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

use Cycle\Schema\Registry;
use Cycle\Schema\SchemaModifierInterface;

abstract class AbstractSchemaModifier implements SchemaModifierInterface
{
    protected string $role;

    /**
     * {@inheritdoc}
     */
    public function withRole(string $role): static
    {
        $relation = clone $this;
        $relation->role = $role;
        return $relation;
    }

    /**
     * {@inheritdoc}
     */
    public function compute(Registry $registry): void
    {
        // ignore this
    }

    /**
     * {@inheritdoc}
     */
    public function render(Registry $registry): void
    {
        // ignore this
    }

    /**
     * {@inheritdoc}
     */
    public function modifySchema(array &$schema): void
    {
        // ignore
    }
}