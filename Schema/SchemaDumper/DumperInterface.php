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

namespace Slince\CycleBundle\Schema\SchemaDumper;

use Cycle\ORM\Schema;

interface DumperInterface
{
    /**
     * Dumps a set of routes to a string representation of executable code
     * that can then be used to generate a URL of such a route.
     */
    public function dump(array $options = []): string;

    /**
     * Gets the routes to dump.
     */
    public function getSchema(): Schema;
}
