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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        // https://symfony.com/doc/current/components/config/definition.html
        $treeBuilder = new TreeBuilder('cycle');
        $rootNode = $treeBuilder->getRootNode();
        $this->addDbalSection($rootNode);
        $this->addOrmSection($rootNode);
        $this->addMigrationSection($rootNode);
        return $treeBuilder;
    }

    private function addOrmSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('orm')
                    ->fixXmlConfig('schema')
                    ->children()
                        ->scalarNode('default')->info('Default entity manager')->end()
                        ->booleanNode('auto_schema')->defaultTrue()->end()
                        ->booleanNode('doctrine')->defaultFalse()->end()
                        ->scalarNode('cache_dir')->defaultValue('%kernel.cache_dir%/cycle')->end()
                        ->arrayNode('generator_classes')->scalarPrototype()->end()->end()
                        ->arrayNode('generator_services')->scalarPrototype()->end()->end()
                        ->arrayNode('schemas')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->arrayPrototype()
                                ->children()
                                    ->enumNode('type')->values(['annotation', 'attribute', 'yaml', 'yml', 'xml'])->end()
                                    ->scalarNode('dir')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->append($this->createRelationsNode())
                    ->end()
                ->end()
            ->end();
    }

    private function createRelationsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('relation');
        $node = $treeBuilder->getRootNode();

        $global = $this->createRelationNode('global');
        $hasOne = $this->createRelationNode('has_one');
        $hasMany = $this->createRelationNode('has_many');
        $belongsTo = $this->createRelationNode('belongs_to');
        $refersTo = $this->createRelationNode('refers_to');
        $manyToMany = $this->createRelationNode('many_to_many');

        $global
            ->children()
            ->scalarNode('collection')->end()
            ->end();

        $hasMany
            ->children()
                ->scalarNode('collection')->end()
            ->end();

        $manyToMany
            ->children()
                ->scalarNode('collection')->end()
            ->end();

        $node
            ->beforeNormalization()
                ->ifTrue(fn ($v) => !array_key_exists('has_one', $v)
                    && !array_key_exists('has_many', $v)
                    && !array_key_exists('belongs_to', $v)
                    && !array_key_exists('refers_to', $v)
                    && !array_key_exists('many_to_many', $v)
                    && !array_key_exists('embedded', $v)
                    && !array_key_exists('global', $v)
                )
                ->then(fn ($v) => ['global' => $v])
            ->end()
            ->children()
                ->append($hasOne)
                ->append($hasMany)
                ->append($belongsTo)
                ->append($refersTo)
                ->append($manyToMany)
                ->append($global)
                ->arrayNode('embedded')
                    ->children()
                        ->enumNode('load')->values(['LAZY', 'EAGER'])->end()
                    ->end()
                ->end()
            ->end();
        return $node;
    }

    private function createRelationNode(string $type): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder($type);
        $node = $treeBuilder->getRootNode();
        $node
            ->children()
                ->enumNode('load')->values(['LAZY', 'EAGER'])->end()
                ->booleanNode('cascade')->end()
                ->booleanNode('nullable')->end()
                ->booleanNode('fk_create')->end()
                ->enumNode('fk_action')->values(['CASCADE', 'SET NULL', 'NO ACTION'])->end()
                ->enumNode('fk_on_delete')->values(['CASCADE', 'SET NULL', 'NO ACTION'])->end()
                ->booleanNode('index_create')->end()
            ->end();
        return $node;
    }

    private function addDbalSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('dbal')
                    ->beforeNormalization()
                        ->ifTrue(fn ($v) => !array_key_exists('default', $v))
                        ->then(static function($v) {
                            $v['default'] = empty($v['databases']) ? 'default': key($v['databases']);
                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(fn ($v) => !array_key_exists('databases', $v) && array_key_exists('database', $v))
                        ->then(static function($v) {
                            $v['databases'] = [$v['default'] => $v['database']];
                            unset($v['database']);
                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(fn ($v) => !array_key_exists('default_connection', $v))
                        ->then(static function($v) {
                            $v['default_connection'] = empty($v['connections']) ? 'default': key($v['connections']);
                            return $v;
                        })
                    ->end()
                    ->beforeNormalization()
                        ->ifTrue(fn ($v) => !array_key_exists('connections', $v) && array_key_exists('connection', $v))
                        ->then(static function($v) {
                            $v['connections'] = [$v['default_connection'] => $v['connection']];
                            unset($v['connection']);
                            return $v;
                        })
                    ->end()
                    ->validate()
                        ->ifTrue(static function($v){
                            return !empty($v['alias']) && !empty(array_diff(array_values($v['alias']), array_keys($v['databases'])));
                        })
                        ->then(static function($v){
                            $available = array_keys($v['databases']);
                            $diff = array_diff(array_values($v['alias']), $available);
                            throw new \InvalidArgumentException(sprintf('Target database "%s" in "alias" is invalid, available "%s"', implode(',', $diff), implode(',', $available)));
                        })
                    ->end()
                    ->fixXmlConfig('connection')
                    ->fixXmlConfig('database')
                    ->fixXmlConfig('alias')
                    ->children()
                        ->scalarNode('default')->info('Default database to use')->end()
                        ->scalarNode('default_connection')->info('Default connection to use')->end()
                        ->arrayNode('alias')
                            ->useAttributeAsKey('alias')
                            ->variablePrototype()->end()
                        ->end()
                        ->arrayNode('databases')
                            ->isRequired()
                            ->requiresAtLeastOneElement()
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('prefix')->end()
                                    ->scalarNode('connection')->end()
                                    ->scalarNode('read_connection')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->append($this->createConnectionNode())
                    ->end()
                ->end()
            ->end();
    }

    private function createConnectionNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('connections');
        $node = $treeBuilder->getRootNode();
        $node
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->validate()
                    ->ifTrue(fn ($v) => empty($v['dbname']) && empty($v['dsn']))
                    ->thenInvalid('One of "dbname" and "dsn" must be provided')
                ->end()
                ->fixXmlConfig('option')
                ->fixXmlConfig('driver_option')
                ->children()
                    ->enumNode('driver')->values(['mysql', 'mariadb', 'sqlite', 'postgresql', 'postgres', 'sqlserver'])->defaultValue('mysql')->end()
                    // basic server information
                    ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                    ->integerNode('port')->defaultNull()->end()
                    ->scalarNode('dbname')->end()
                    ->scalarNode('user')->defaultValue('root')->end()
                    ->scalarNode('password')->defaultValue('')->end()
                    ->scalarNode('unix_socket')->info('The unix socket to use for MySQL')->end()
                    ->scalarNode('path')->info('The path to use for SQLite')->end()
                    ->booleanNode('memory')->info('The memory to use for SQLite')->defaultFalse()->end()
                    ->scalarNode('dsn')->end()
                    ->scalarNode('charset')->info('The charset to use for MySQL')->defaultNull()->end()
                    ->scalarNode('driver_class')->info('The driver_class to use for MySQL')->defaultNull()->end()
                    // for SQLServer
                    ->scalarNode('app')->info('The "APP" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('pooling')->info('The "ConnectionPooling" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('encrypt')->info('The "Encrypt" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('failover')->info('The "Failover_Partner" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('timeout')->info('The "LoginTimeout" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('mars')->info('The "MultipleActiveResultSets" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('quoted')->info('The "QuotedId" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('trace_file')->info('The "TraceFile" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('trace')->info('The "TraceOn" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('isolation')->info('The "TransactionIsolation" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('trust_server_certificate')->info('The "TrustServerCertificate" to use for SQLServer')->defaultNull()->end()
                    ->scalarNode('wsid')->info('The "WSID" to use for SQLServer')->end()
                    // driver config
                    ->booleanNode('reconnect')->defaultTrue()->end()
                    ->scalarNode('timezone')->defaultValue('UTC')->end()
                    ->booleanNode('query_cache')->defaultTrue()->end()
                    ->booleanNode('readonly_schema')->defaultFalse()->end()
                    ->booleanNode('readonly')->defaultFalse()->end()
                    ->arrayNode('driver_options')
                        ->useAttributeAsKey('name')
                        ->variablePrototype()->end()
                    ->end()
                    ->arrayNode('options')
                        ->useAttributeAsKey('name')
                        ->variablePrototype()->end()
                    ->end()
                ->end()
            ->end();
        return $node;
    }

    private function addMigrationSection(ArrayNodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('migration')
                    ->children()
                        ->scalarNode('directory')->defaultValue('%kernel.project_dir%/migrations')->end()
                        ->arrayNode('vendor_directories')->scalarPrototype()->end()->end()
                        ->scalarNode('table')->defaultValue('migrations')->end()
                        ->booleanNode('safe')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();
    }
}
