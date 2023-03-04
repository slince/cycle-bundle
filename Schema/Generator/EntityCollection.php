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

namespace Slince\CycleBundle\Schema\Generator;

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

class EntityCollection implements GeneratorInterface
{
    /**
     * @var Entity[]
     */
    private array $entities;

    public function __construct(array $entities = [])
    {
        $this->entities = $entities;
    }

    public function register(Entity $entity): void
    {
        $this->entities[] = $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Registry $registry): Registry
    {
        foreach ($this->entities as $entity) {
            $registry->register($entity);
            $registry->linkTable($entity, $entity->getDatabase(), $entity->getTableName());
        }
        return $registry;
    }
}