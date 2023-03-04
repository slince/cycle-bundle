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

namespace Slince\CycleBundle\Pagerfanta;

use Cycle\ORM\Select;
use Pagerfanta\Adapter\AdapterInterface;

class QueryAdapter implements AdapterInterface
{
    private Select $select;

    public function __construct(Select $select)
    {
        $this->select = $select;
    }

    public function getNbResults(): int
    {
        return count($this->select);
    }

    public function getSlice(int $offset, int $length): iterable
    {
        $this->select->limit($length);
        $this->select->offset($offset);
        return $this->select;
    }
}