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
namespace Slince\CycleBundle\Schema\Loader;

use Slince\CycleBundle\Schema\GeneratorCollection;
use Symfony\Component\Config\Loader\Loader;

class ClosureLoader extends Loader
{
    /**
     * Loads a Closure.
     */
    public function load(mixed $closure, string $type = null): GeneratorCollection
    {
        return $closure($this, $this->env);
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return $resource instanceof \Closure && (!$type || 'closure' === $type);
    }
}
