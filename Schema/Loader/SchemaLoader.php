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
use Symfony\Component\Config\Loader\Loader;

class SchemaLoader extends Loader
{
    protected array $resources;

    public function __construct(array $resources, string $env = null)
    {
        $this->resources = $resources;
        parent::__construct($env);
    }

    /**
     * {@inheritdoc}
     */
    public function load(mixed $resource, string $type = null): GeneratorCollection
    {
        $collection = new GeneratorCollection();
        foreach ($this->resources as $resource) {
            $subCollection = $this->import($resource['dir'], $resource['type'] ?? null);
            $collection->addCollection($subCollection);
        }
        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(mixed $resource, string $type = null): bool
    {
        return $type === 'schema_resources';
    }
}