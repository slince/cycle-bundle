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
use Symfony\Component\Config\Resource\FileResource;
use function Slince\CycleBundle\ORM\Schema\Loader\get_debug_type;

abstract class ObjectLoader extends Loader
{

    abstract protected function getObject(string $id): object;

    /**
     * Calls the object method that will load the routes.
     */
    public function load(mixed $resource, string $type = null): GeneratorCollection
    {
        if (!preg_match('/^[^\:]+(?:::(?:[^\:]+))?$/', $resource)) {
            throw new \InvalidArgumentException(sprintf('Invalid resource "%s" passed to the %s route loader: use the format "object_id::method" or "object_id" if your object class has an "__invoke" method.', $resource, \is_string($type) ? '"'.$type.'"' : 'object'));
        }

        $parts = explode('::', $resource);
        $method = $parts[1] ?? '__invoke';

        $loaderObject = $this->getObject($parts[0]);

        if (!\is_object($loaderObject)) {
            throw new \TypeError(sprintf('"%s:getObject()" must return an object: "%s" returned.', static::class, get_debug_type($loaderObject)));
        }

        if (!\is_callable([$loaderObject, $method])) {
            throw new \BadMethodCallException(sprintf('Method "%s" not found on "%s" when importing routing resource "%s".', $method, get_debug_type($loaderObject), $resource));
        }

        $routeCollection = $loaderObject->$method($this, $this->env);

        if (!$routeCollection instanceof GeneratorCollection) {
            $type = get_debug_type($routeCollection);

            throw new \LogicException(sprintf('The "%s::%s()" method must return a GeneratorCollection: "%s" returned.', get_debug_type($loaderObject), $method, $type));
        }

        // make the object file tracked so that if it changes, the cache rebuilds
        $this->addClassResource(new \ReflectionClass($loaderObject), $routeCollection);

        return $routeCollection;
    }

    private function addClassResource(\ReflectionClass $class, GeneratorCollection $collection)
    {
        do {
            if (is_file($class->getFileName())) {
                $collection->addResource(new FileResource($class->getFileName()));
            }
        } while ($class = $class->getParentClass());
    }
}
