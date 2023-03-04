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

use Cycle\Schema\GeneratorInterface;
use Symfony\Component\Config\Resource\ResourceInterface;

class GeneratorCollection implements \IteratorAggregate, \Countable, \ArrayAccess
{
    protected array $generators = [];
    protected array $sorted = [];

    /**
     * @var array
     */
    private array $resources = [];

    public function __construct(array $generators = [], array $resources = [])
    {
        $this->generators = $generators;
        $this->resources = $resources;
    }

    public function count(): int
    {
        return count($this->generators);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->generators);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->generators[$offset]);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset): ?GeneratorInterface
    {
        return $this->generators[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->generators[$offset] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->generators[$offset]);
    }

    /**
     * Add a generator.
     *
     * @param GeneratorInterface $generator
     * @param int $priority
     * @return void
     */
    public function add(GeneratorInterface $generator, int $priority = 0): void
    {
        $this->generators[] = $generator;
        if (!isset($this->sorted[$priority])) {
            $this->sorted[$priority] = [];
        }
        $this->sorted[$priority][] = $generator;
    }

    /**
     * Add some generators.
     *
     * @param array $generators
     * @param int $priority
     * @return void
     */
    public function addAll(array $generators, int $priority = 0): void
    {
        foreach ($generators as $generator) {
            $this->add($generator, $priority);
        }
    }

    /**
     * Returns generators matched the filter.
     *
     * @param \Closure $callback
     * @return array
     */
    public function filter(\Closure $callback): array
    {
        return array_filter($this->generators, $callback);
    }

    /**
     * Return the sorted generator group.
     *
     * @return array
     */
    public function sorted(): array
    {
        return $this->sorted;
    }

    /**
     * @return GeneratorInterface[]
     */
    public function all(): array
    {
        return $this->generators;
    }

    /**
     * Sort generators.
     *
     * @return GeneratorCollection
     */
    public function sort(): self
    {
        ksort($this->sorted);
        $this->generators = array_reduce($this->sorted, 'array_merge', []);
        return $this;
    }

    /**
     * 返回加载的资源
     *
     * @return ResourceInterface[] An array of resources
     */
    public function getResources(): array
    {
        return array_values($this->resources);
    }

    /**
     * 添加GeneratorInterface集合
     * @param GeneratorCollection $collection
     */
    public function addCollection(GeneratorCollection $collection)
    {
        foreach ($collection->sorted() as $priority => $sorted) {
            $this->addAll($sorted, $priority);
        }

        foreach ($collection->getResources() as $resource) {
            $this->addResource($resource);
        }
    }

    /**
     * 添加资源为当前GeneratorInterface集合
     *
     * @param ResourceInterface $resource
     */
    public function addResource(ResourceInterface $resource)
    {
        $key = (string) $resource;

        if (!isset($this->resources[$key])) {
            $this->resources[$key] = $resource;
        }
    }
}