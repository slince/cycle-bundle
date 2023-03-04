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

namespace Slince\CycleBundle\Schema\Registry;

use Cycle\Database\DatabaseManager;
use Cycle\Schema\Registry;

class RegistryFactory
{
    private DatabaseManager $dbal;

    public function __construct(DatabaseManager $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * Create one schema registry.
     *
     * @return Registry
     */
    public function create(): Registry
    {
        return new Registry($this->dbal);
    }
}