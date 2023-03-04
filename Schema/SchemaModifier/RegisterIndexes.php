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

namespace Slince\CycleBundle\Schema\SchemaModifier;

use Slince\CycleBundle\Schema\Table\Index;
use Slince\CycleBundle\Schema\Table\Table;
use Cycle\Annotated\Exception\AnnotationException;
use Cycle\Database\Schema\AbstractTable;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Registry;

class RegisterIndexes extends AbstractSchemaModifier
{

    private Table $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function render(Registry $registry): void
    {
        $entity = $registry->getEntity($this->role);
        $table = $registry->getTableSchema($entity);
        $this->registerIndexes($table, $entity, $this->table->getIndexes());
    }

    /**
     * @param AbstractTable $table
     * @param Entity $entity
     * @param Index[] $indexes
     * @return void
     */
    protected function registerIndexes(AbstractTable $table, Entity $entity, array $indexes): void
    {
        foreach ($indexes as $index) {
            if ($index->getColumns() === []) {
                throw new AnnotationException(
                    "Invalid index definition for `{$entity->getRole()}`. Column list can't be empty."
                );
            }

            $indexSchema = $table->index($index->getColumns());
            $indexSchema->unique($index->isUnique());

            if ($index->getName() !== null) {
                $indexSchema->setName($index->getName());
            }
        }
    }
}