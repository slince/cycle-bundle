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

use Psr\Container\ContainerInterface;

class ContainerLoader extends ObjectLoader
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, string $env = null)
    {
        $this->container = $container;
        parent::__construct($env);
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return 'service' === $type && \is_string($resource);
    }

    protected function getObject(string $id): object
    {
        return $this->container->get($id);
    }
}
