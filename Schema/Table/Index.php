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

class Index
{
    private readonly array $columns;
    private readonly bool $unique;
    private readonly ?string $name;

    /**
     * @param non-empty-string[] $columns
     * @param non-empty-string|null $name
     */
    public function __construct(array $columns, bool $unique = false, ?string $name = null)
    {
        $this->columns = $columns;
        $this->unique = $unique;
        $this->name = $name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }
}