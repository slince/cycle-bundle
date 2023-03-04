<?php

declare(strict_types=1);

/*
 * This file is part of the slince/cycle-bundle package.
 *
 * (c) Slince <taosikai@yeah.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Slince\CycleBundle\Schema;

use Slince\CycleBundle\Schema\Generator\DelegatingGenerator;
use Slince\CycleBundle\Schema\SchemaDumper\DumperInterface;
use Slince\CycleBundle\Schema\SchemaDumper\PhpNativeDumper;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Config\ConfigCacheFactory;
use Symfony\Component\Config\ConfigCacheFactoryInterface;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class SchemaManager implements ServiceSubscriberInterface, CacheWarmerInterface, CacheClearerInterface
{
    private ContainerInterface $container;

    /**
     * @var mixed
     */
    protected mixed $resource;

    /**
     * @var array
     */
    protected array $options = [];

    protected ?GeneratorCollection $collection = null;
    protected ?DumperInterface $dumper = null;
    protected ?ConfigCacheFactory $configCacheFactory = null;
    protected ?Schema $schema = null;
    protected ?GeneratorInterface $generators = null;
    protected array $resources;

    public function __construct(ContainerInterface $container, mixed $resource, array $options = [])
    {
        $this->container = $container;
        $this->resource = $resource;
        $this->setOptions($options);
    }

    /**
     * Sets options.
     *
     * Available options:
     *
     *   * cache_dir:              The cache directory (or null to disable caching)
     *   * debug:                  Whether to enable debugging or not (false by default)
     *   * dumper_class: The name of a Dumper implementation
     *   * resource_type:          Type hint for the main resource (optional)
     *   * strict_requirements:    Configure strict requirement checking for generators
     *                             implementing ConfigurableRequirementsInterface (default is true)
     *
     * @throws \InvalidArgumentException When unsupported option is provided
     */
    public function setOptions(array $options)
    {
        $this->options = [
            'cache_dir' => null,
            'debug' => false,
            'dumper_class' => PhpNativeDumper::class,
            'resource_type' => null,
            'strict_requirements' => true,
        ];

        // check option names and live merge, if errors are encountered Exception will be thrown
        $invalid = [];
        foreach ($options as $key => $value) {
            if (\array_key_exists($key, $this->options)) {
                $this->options[$key] = $value;
            } else {
                $invalid[] = $key;
            }
        }

        if ($invalid) {
            throw new \InvalidArgumentException(sprintf('The Schema does not support the following options: "%s".', implode('", "', $invalid)));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $cacheDir)
    {
        @unlink($this->options['cache_dir'] . '/cycle_schemas.php');
    }

    /**
     * Returns the schema of the registry.
     *
     * @return Schema
     */
    public function getSchema(): Schema
    {
        if (null !== $this->schema) {
            return $this->schema;
        }
        $cache = $this->getConfigCacheFactory()->cache($this->options['cache_dir'] . '/cycle_schemas.php',
            function (ConfigCacheInterface $cache){
                $collection = $this->getGeneratorCollection();
                $cache->write($this->getDumper()->dump($this->options), $collection->getResources());
            }
        );
        return $this->schema = include $cache->getPath();
    }

    /**
     * Compile schema with all generators.
     *
     * @param Registry $registry
     * @param array $generators temporary generators.
     * @return Schema
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function compile(Registry $registry, array $generators = []): Schema
    {
        $generators = [$this->buildGenerators(), ...$generators];
        return $this->compileWith($registry, $generators);
    }

    /**
     * Compile schema with given generators.
     *
     * @param Registry $registry
     * @param array $generators temporary generators.
     * @return Schema
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function compileWith(Registry $registry, array $generators = []): Schema
    {
        $data = $this->container->get('cycle.schema.compiler')->compile($registry, $generators);
        return new Schema($data);
    }

    /**
     * 创建默认的generators
     * @return GeneratorInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function buildGenerators(): GeneratorInterface
    {
        if (null !== $this->generators) {
            return $this->generators;
        }
        $collection = new GeneratorCollection();
        $collection->addCollection($this->getGeneratorCollection());
        $collection->addCollection($this->container->get('cycle.schema.generators'));

        $generators = $collection->sort()->all();

        return $this->generators = new DelegatingGenerator($generators);
    }

    /**
     * Returns generator collection.
     *
     * @return GeneratorCollection|null
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getGeneratorCollection(): ?GeneratorCollection
    {
        return $this->collection ??= $this->container->get('cycle.schema.loader')->load($this->resource, $this->options['resource_type']);
    }

    /**
     * Returns schema dumper.
     * @return DumperInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getDumper(): DumperInterface
    {
        $registry = $this->container->get('cycle.schema.registry');
        return $this->dumper ??= new $this->options['dumper_class']($this->compile($registry));
    }

    /**
     * Provides the ConfigCache factory implementation, falling back to a
     * default implementation if necessary.
     */
    protected function getConfigCacheFactory(): ConfigCacheFactoryInterface
    {
        if (null === $this->configCacheFactory) {
            $this->configCacheFactory = new ConfigCacheFactory($this->options['debug']);
        }
        return $this->configCacheFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp(string $cacheDir): array
    {
        $this->schema = null;
        $this->getSchema();
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'cycle.schema.loader' => LoaderInterface::class,
            'cycle.schema.registry' => Registry::class,
            'cycle.schema.compiler' => Compiler::class,
            'cycle.schema.generators' => GeneratorCollection::class,
        ];
    }
}