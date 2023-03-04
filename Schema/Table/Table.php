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

namespace Slince\CycleBundle\Schema\Table;

use Cycle\Schema\Definition\Entity;

class Table
{
    private Entity $entity;

    /**
     * @var Index[]
     */
    private array $indexes;

    public function __construct(Entity $entity, array $indexes = [])
    {
        $this->entity = $entity;
        $this->indexes = $indexes;
    }

    /**
     * @return Entity
     */
    public function getEntity(): Entity
    {
        return $this->entity;
    }

    public function addIndex(Index $index): void
    {
        $this->indexes[] = $index;
    }

    /**
     * @return array
     */
    public function getIndexes(): array
    {
        return $this->indexes;
    }
}