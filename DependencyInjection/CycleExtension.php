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

namespace Slince\CycleBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class CycleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config  = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $this->configureDbal($container, $config['dbal']);
        $this->configureOrm($container, $config['orm']);
        $this->configureMigration($container, $config['migration']);
    }

    private function configureDbal(ContainerBuilder $container, array $configs)
    {
        foreach ($configs['connections'] as $name => $config) {
            $connection = new ChildDefinition('cycle.dbal.connection_config');
            $connection->addArgument($config);
            $container->setDefinition("cycle.dbal.connection_config.{$name}", $connection);

            $driver = new ChildDefinition('cycle.dbal.driver_config');
            $driver->addArgument($config)->addTag('cycle.dbal.driver_config', ['name' => $name]);
            $container->setDefinition("cycle.dbal.driver_config.{$name}", $driver);
        }
        $container->addAliases([
            "cycle.dbal.default_connection_config" => "cycle.dbal.connection_config.{$configs['default_connection']}",
            "cycle.dbal.default_driver_config" => "cycle.dbal.driver_config.{$configs['default_connection']}"
        ]);

        $container->findDefinition('cycle.dbal.database_config')->setArgument('$config', $configs);
    }

    private function configureOrm(ContainerBuilder $container, array $configs)
    {
        $container->setParameter('cycle.schema.cache_dir', $configs['cache_dir']);
        // generator
        if (empty($configs['generator_classes'])) {
            $configs['generator_classes'] = $container->getParameter('cycle.schema.generator_classes');
        }
        foreach ($configs['generator_classes'] as $generatorClass) {
            $definition = new Definition($generatorClass);
            $container->setDefinition($generatorClass, $definition)->addTag('cycle.schema.generator', ['priority' => 10]);
        }
        foreach ($configs['generator_services'] as $generatorService) {
            $container->findDefinition($generatorService)->addTag('cycle.schema.generator', ['priority' => 10]);
        }
        $container->findDefinition('cycle.schema.generator.apply_relation_defaults')
            ->setArgument('$defaults', $configs['relation'] ?? []);

        if ($configs['auto_schema']) {
            foreach ($container->getParameter('kernel.bundles_metadata') as $bundle) {
                $configDir = is_dir($bundle['path'].'/Resources/config') ? $bundle['path'].'/Resources/config' : $bundle['path'].'/config';

                if ($container->fileExists($dir = $configDir.'/cycle', '/^$/')) {
                    $configs['schemas'][] = ['dir' => $dir, 'type' => 'directory'];
                }

                if ($configs['doctrine'] && $container->fileExists($dir = $configDir.'/doctrine', '/^$/')) {
                    $configs['schemas'][] = ['dir' => $dir, 'type' => 'directory'];
                }
            }
        }
        $container->setParameter('cycle.schema.resources', $configs['schemas']);
    }

    private function configureMigration(ContainerBuilder $container, array $configs)
    {
        $container->setParameter('cycle.migration.config', $configs);
    }
}