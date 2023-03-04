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

use Cycle\Schema\Definition\Entity;
use Cycle\Schema\GeneratorInterface;
use Cycle\Schema\Registry;
use Doctrine\Inflector\Inflector;

class ApplyRelationDefaults implements GeneratorInterface
{
    protected Inflector $inflector;
    protected array $defaults;

    public function __construct(Inflector $inflector, array $defaults)
    {
        $this->inflector = $inflector;
        $this->defaults = $defaults;
    }

    public function run(Registry $registry): Registry
    {
        $global = $this->defaults['global'] ?? [];

        $this->applyRelationDefaults($registry, 'has_one', $global);
        $this->applyRelationDefaults($registry, 'has_many', $global);
        $this->applyRelationDefaults($registry, 'belongs_to', $global);
        $this->applyRelationDefaults($registry, 'refers_to', $global);
        $this->applyRelationDefaults($registry, 'many_to_many', $global);
        $this->applyRelationDefaults($registry, 'embedded', $global);

        return $registry;
    }

    private function applyRelationDefaults(Registry $registry, string $type, array $global)
    {
        $defaults = array_merge($global, $this->defaults[$type] ?? []);
        if (empty($defaults)) {
            return;
        }
        $defaults = $this->changeDefaultsKey($defaults);
        foreach ($registry as $entity) {
            $this->modifyRelationDefaults($entity, $this->inflector->camelize($type), $defaults);
        }
    }

    private function changeDefaultsKey(array $array): array
    {
        $converted = [];
        foreach ($array as $key => $value) {
            $converted[$this->inflector->camelize($key)] = $value;
        }
        return $converted;
    }

    private function modifyRelationDefaults(Entity $entity, string $type, array $defaults)
    {
        foreach ($entity->getRelations() as $relation) {
            if (strcasecmp($type, $relation->getType()) !== 0) {
                continue;
            }
            foreach ($defaults as $key => $default) {
                // Skip if present.
                if ($relation->getOptions()->has($key)) {
                    continue;
                }
                $relation->getOptions()->set($key, $default);
            }
        }
    }
}