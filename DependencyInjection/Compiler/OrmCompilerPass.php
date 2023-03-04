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

namespace Slince\CycleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OrmCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {

        // Dbal driver config
        $databaseConfig = $container->findDefinition('cycle.dbal.database_config');
        $driverConfigs = $container->findTaggedServiceIds('cycle.dbal.driver_config');
        $connections = [];
        foreach ($driverConfigs as $id => $tags) {
            foreach ($tags as $tag) {
                $connections[$tag['name']] = new Reference($id);
            }
        }
        $databaseConfig->setArgument('$connections', $connections);

        // Schema loader
        $resolver = $container->findDefinition('cycle.schema.loader.resolver');
        $loaders = $container->findTaggedServiceIds('cycle.schema.loader');
        foreach ($loaders as $id => $tags) {
            $resolver->addMethodCall('addLoader', [new Reference($id)]);
        }

        // Schema generator
        $generatorCollection = $container->findDefinition('cycle.schema.generators');
        $generators = $container->findTaggedServiceIds('cycle.schema.generator');
        foreach ($generators as $id => $tags) {
            foreach ($tags as $tag) {
                $generatorCollection->addMethodCall('add', [new Reference($id), $tag['priority'] ?? 10]);
            }
        }
    }
}