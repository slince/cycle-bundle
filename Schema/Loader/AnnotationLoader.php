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

namespace Slince\CycleBundle\Schema\Loader;

use Slince\CycleBundle\Schema\GeneratorCollection;
use Cycle\Annotated\Embeddings;
use Cycle\Annotated\Entities;
use Spiral\Tokenizer\ClassLocator;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Finder\Finder;

class AnnotationLoader extends FileLoader
{
    /**
     * {@inheritdoc}
     */
    public function load(mixed $file, string $type = null): GeneratorCollection
    {
        $path = $this->locator->locate($file);
        $finder = (new Finder())->files()->in([$path]);
        $classLocator = new ClassLocator($finder);
        $generators = new GeneratorCollection();
        $generators->add(new Embeddings($classLocator));
        $generators->add(new Entities($classLocator));
        $generators->addResource(new DirectoryResource($path));

        return $generators;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return \is_string($resource) && \in_array($type, ['annotation', 'attribute'], true);
    }

    public function setResolver(LoaderResolverInterface $resolver)
    {
    }

    public function getResolver(): LoaderResolverInterface
    {
    }
}