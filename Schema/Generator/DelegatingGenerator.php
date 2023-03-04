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

use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;

class DelegatingGenerator implements GeneratorInterface
{
    private array $generators;

    public function __construct(array $generators)
    {
        $this->generators = $generators;
    }

    /**
     * {@inheritdoc}
     */
    public function run(Registry $registry): Registry
    {
        foreach ($this->generators as $generator) {
            $registry = $generator->run($registry);
        }
        return $registry;
    }
}