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
use Symfony\Component\Config\Loader\FileLoader;

class GlobFileLoader extends FileLoader
{
    public function load(mixed $resource, string $type = null): GeneratorCollection
    {
        $collection = new GeneratorCollection();

        foreach ($this->glob($resource, false, $globResource) as $path => $info) {
            $collection->addCollection($this->import($path));
        }

        $collection->addResource($globResource);

        return $collection;
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return 'glob' === $type;
    }
}
