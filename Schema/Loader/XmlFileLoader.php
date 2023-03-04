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

use Slince\CycleBundle\Schema\Generator\EntityCollection;
use Slince\CycleBundle\Schema\GeneratorCollection;
use Slince\CycleBundle\Schema\SchemaModifier\RegisterIndexes;
use Slince\CycleBundle\Schema\Table;
use Cycle\ORM\Entity\Behavior;
use Cycle\Schema\Definition\Entity;
use Cycle\Schema\Definition\Field;
use Cycle\Schema\Definition\Relation;
use Cycle\Schema\Table\Column;
use Doctrine\Inflector\Inflector;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Util\XmlUtils;


class XmlFileLoader extends FileLoader
{
    public const NAMESPACE_URI = 'http://cycle.dev/schema/mapping';
    public const SCHEME_PATH = '/schema/schema/schema-1.0.xsd';
    private Inflector $inflector;

    public function __construct(FileLocatorInterface $locator, Inflector $inflector, string $env = null)
    {
        parent::__construct($locator, $env);
        $this->inflector = $inflector;
    }

    public function load(mixed $file, string $type = null): GeneratorCollection
    {
        $path = $this->locator->locate($file);

        $xml = $this->loadFile($path);

        $collection = new GeneratorCollection();
        $collection->addResource(new FileResource($path));

        // process entities and imports
        foreach ($xml->documentElement->childNodes as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $this->parseNode($collection, $node, $path, $file);
        }

        return $collection;
    }

    protected function parseNode(GeneratorCollection $collection, \DOMElement $node, string $path, string $file)
    {
        if (self::NAMESPACE_URI !== $node->namespaceURI) {
            return;
        }
        $entities = new EntityCollection();
        switch ($node->localName) {
            case 'embeddable':
                $this->parseEmbedding($entities, $node, $path);
                break;
            case 'entity':
                $this->parseEntity($entities, $node, $path);
                break;
            case 'import':
                $this->parseImport($collection, $node, $path, $file);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s". Expected "route" or "import".', $node->localName, $path));
        }
        $collection->add($entities);
    }

    protected function parseEmbedding(EntityCollection $entities, \DOMElement $node, string $path): Entity
    {
        if ('' === $name = $node->getAttribute('name')) {
            throw new \InvalidArgumentException(sprintf('The <embeddable> element in file "%s" must have an "name" attribute.', $path));
        }
        $entity = new Entity();
        $entity->setClass($name);
        $entity->setRole($node->getAttribute('role') ?? $this->inflector->camelize(basename($name)));
        $entity->setMapper($node->getAttribute('mapper'));

        $table = new Table\Table($entity);

        foreach ($node->getElementsByTagNameNS(static::NAMESPACE_URI, 'field') as $child) {
            $this->parseField($entity, $table, $child, false, $path);
        }
        $entities->register($entity);
        return $entity;
    }

    protected function parseEntity(EntityCollection $collection, \DOMElement $node, string $path): Entity
    {
        if ('' === $name = $node->getAttribute('name')) {
            throw new \InvalidArgumentException(sprintf('The <entity> element in file "%s" must have an "name" attribute.', $path));
        }
        $shortName = ltrim(strrchr($name, '\\'), '\\');
        $entity = new Entity();
        $entity->setClass($name);
        $entity->setRole($node->getAttribute('role') ?: $this->inflector->camelize($shortName));
        $entity->setMapper($node->getAttribute('mapper') ?: null);
        $entity->setRepository($node->getAttribute('repository-class') ?: $node->getAttribute('repository'));
        $entity->setSource($node->getAttribute('source') ?: null);
        $entity->setScope($node->getAttribute('scope') ?: null);
        $entity->setDatabase($node->getAttribute('database') ?: null);
        $entity->setTableName($node->getAttribute('table') ?: $this->inflector->tableize($entity->getRole()));

        $table = new Table\Table($entity);
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            switch ($child->localName) {
                case 'indexes':
                case 'unique-constraints':
                    $this->parseIndexes($table, $child, $path);
                    break;
                case 'options':
                    foreach (XmlUtils::convertDomElementToArray($child)['option'] as $option) {
                        $entity->getOptions()->set($option['name'], $option['value']);
                    }
                    break;
                case 'typecasts':
                    $typecasts = XmlUtils::convertDomElementToArray($child);
                    $entity->setTypecast($typecasts['typecast']);
                    break;
                case 'behaviors':
                    $this->parseBehaviors($entity, $child, $path);
                    break;
                case 'id':
                    $this->parseField($entity, $table, $child, true, $path);
                    break;
                case 'field':
                    $this->parseField($entity, $table, $child, false, $path);
                    break;
                case 'embedded':
                case 'has-one':
                case 'has-many':
                case 'belongs-to':
                case 'many-to-many':
                case 'refers-to':
                    $this->parseRelation($entity, $child, $path);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s".', $child->localName, $path));
            }
        }
        $entity->addSchemaModifier(new RegisterIndexes($table));
        $collection->register($entity);
        return $entity;
    }

    protected function parseIndexes(Table\Table $table, \DOMElement $node, string $path): void
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            switch ($child->localName) {
                case 'index':
                    $raw = $child->getAttribute('columns');
                    if (empty($raw)) {
                        throw new \InvalidArgumentException(sprintf('The <index> element in file "%s" must have an "columns" attribute.', $path));
                    }
                    $columns = explode(',', $raw);
                    $index = new Table\Index($columns, false, $child->getAttribute('name') ?: null);
                    break;
                case 'unique-constraint':
                    $raw = $child->getAttribute('columns');
                    if (empty($raw)) {
                        throw new \InvalidArgumentException(sprintf('The <unique-constraint> element in file "%s" must have an "columns" attribute.', $path));
                    }
                    $columns = explode(',', $raw);
                    $index = new Table\Index($columns, true, $child->getAttribute('name') ?: null);
                    break;
                default:
                    throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s".', $child->localName, $path));
            }
            $table->addIndex($index);
        }
    }

    protected function parseBehaviors(Entity $entity, \DOMElement $node, string $path)
    {
        foreach ($node->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }
            $behavior = match ($child->localName) {
                'event-listener' => new Behavior\EventListener(
                    $child->getAttribute('listener'),
                ),
                'created-at' => new Behavior\CreatedAt(
                    $child->getAttribute('field'),
                    $child->getAttribute('column') ?: null
                ),
                'updated-at' => new Behavior\UpdatedAt(
                    $child->getAttribute('field'),
                    $child->getAttribute('column') ?: null,
                    XmlUtils::phpize($child->getAttribute('nullable')) ?: false
                ),
                'soft-delete' => new Behavior\SoftDelete(
                    $child->getAttribute('field'),
                    $child->getAttribute('column') ?: null
                ),
                'optimistic-lock' => new Behavior\OptimisticLock(
                    $child->getAttribute('field'),
                    $child->getAttribute('column') ?: null,
                    $child->getAttribute('rule') ?: null,
                ),
                default => throw new \InvalidArgumentException(sprintf('Unknown tag "%s" used in file "%s".', $child->localName, $path)),
            };
            $entity->addSchemaModifier($behavior);
        }
    }

    protected function parseField(Entity $entity, Table\Table $table, \DOMElement $node, bool $isPrimary, string $path): Field
    {
        $field = new Field();
        $name = $node->getAttribute('name');
        $field->setType($node->getAttribute('type'));
        $field->setColumn($node->getAttribute('column') ?: $this->inflector->tableize($name));
        $field->setPrimary($isPrimary);
        $field->setTypecast($node->getAttribute('typecast') ?: null);

        $generators = $node->getElementsByTagName('generator');
        if ($generators->count() > 0 && strcasecmp($generators[0]->getAttribute('strategy'), 'auto') == 0) {
            $type = match($field->getType()) {
                'int', 'integer' => 'primary',
                'bigint' => 'bigPrimary',
                default => throw new \InvalidArgumentException(sprintf('Unknown field type "%s" used in file "%s".', $field->getType(), $path))
            };
            $field->setType($type);
        }

        if ($nullable = XmlUtils::phpize($node->getAttribute('nullable'))) {
            $field->getOptions()->set(Column::OPT_NULLABLE, $nullable);
            $field->getOptions()->set(Column::OPT_DEFAULT, null);
        }

        if (!$nullable) {
            $default = XmlUtils::phpize($node->getAttribute('default'));
            $field->getOptions()->set(Column::OPT_DEFAULT, $default);
        }

        if (XmlUtils::phpize($node->getAttribute('cast-default'))) {
            $field->getOptions()->set(Column::OPT_CAST_DEFAULT, true);
        }

        // process length
        $length = XmlUtils::phpize($node->getAttribute('length'));
        if (is_int($length) && $length > 0) {
            $field->setType(sprintf('%s(%d)', $field->getType(), $length));
        }

        // process decimal
        $precision = XmlUtils::phpize($node->getAttribute('precision'));
        $scale = XmlUtils::phpize($node->getAttribute('scale'));

        if (is_int($precision) && $precision > 0 && is_int($scale) && $scale > 0) {
            $field->setType(sprintf('%s(%d,%d)', $field->getType(), $precision, $scale));
        }

        if ($comment = XmlUtils::phpize($node->getAttribute('comment'))) {
            $field->getAttributes()->set('comment', $comment);
        }

        if (XmlUtils::phpize($node->getAttribute('unique'))) {
            $table->addIndex(new Table\Index([$field->getColumn()], true, $node->getAttribute('unique-key-name') ?: null));
        }

        $optionNodes = $node->getElementsByTagName('options');
        foreach ($optionNodes as $optionNode) {
            foreach (XmlUtils::convertDomElementToArray($optionNode)['option'] as $option) {
                $field->getOptions()->set($option['name'], $option['value']);
            }
        }

        $field->setEntityClass($entity->getClass());
        $entity->getFields()->set($name, $field);

        return $field;
    }

    protected function parseRelation(Entity $entity, \DOMElement $node, string $path)
    {
        $relation = new Relation();
        $relation->setTarget($node->getAttribute('target'));
        $options = [
            'load' => $node->getAttribute('load'),
            'cascade' => $node->getAttribute('cascade'),
            'nullable' => $node->getAttribute('nullable'),
            'innerKey' => $node->getAttribute('inner-key'),
            'outerKey' => $node->getAttribute('outer-key'),
            'fkCreate' => $node->getAttribute('fk-create'),
            'fkAction' => $node->getAttribute('fk-action'),
            'fkOnDelete' => $node->getAttribute('fk-on-delete') ?? $node->getAttribute('fk-action'),
            'indexCreate' => $node->getAttribute('index-create'),
        ];
        switch ($node->localName) {
            case 'has-one':
                $type = 'hasOne';
                break;
            case 'has-many':
                $type = 'hasMany';
                $options['collection'] = $node->getAttribute('collection');
                $options['where'] = $this->parseWhere($node->getElementsByTagName('where'));
                $options['orderBy'] = $this->parseOrderBy($node->getElementsByTagName('order-by'));
                break;
            case 'belongs-to':
                $type = 'belongsTo';
                break;
            case 'refers-to':
                $type = 'refers-to';
                break;
            case 'many-to-many':
                $type = 'manyToMany';
                $options['collection'] = $node->getAttribute('collection');
                $options['throughInnerKey'] = $node->getAttribute('through-inner-key');
                $options['throughOuterKey'] = $node->getAttribute('through-outer-key');
                $options['where'] = $this->parseWhere($node->getElementsByTagName('where'));
                $options['throughWhere'] = $this->parseWhere($node->getElementsByTagName('through-where'));
                $options['orderBy'] = $this->parseOrderBy($node->getElementsByTagName('order-by'));
                break;
            case 'embedded':
                $type = 'embedded';
                $options['embeddedPrefix'] = $node->getAttribute('prefix');
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown relation type %s in file "%s"', $node->localName, $path));
        }
        foreach ($options as $option => $value) {
            if (null === $value || '' === $value) {
                continue;
            }
            $relation->getOptions()->set($option, $value);
        }
        $relation->setType($type);
        $field = $node->getAttribute('field');
        $entity->getRelations()->set($field, $relation);
    }

    protected function parseWhere(\DOMNodeList $nodes): array
    {
        $where = [];
        foreach ($nodes as $node) {
            $whereFields = $node->getElementsByTagName('where-field');
            foreach ($whereFields as $field) {
                $where[$field->getAttribute('name')] = XmlUtils::phpize($field->getAttribute('value'));
            }
        }
        return $where;
    }


    protected function parseOrderBy(\DOMNodeList $nodes): array
    {
        $orderBy = [];
        foreach ($nodes as $node) {
            $orderByFields = $node->getElementsByTagName('order-by-field');
            foreach ($orderByFields as $field) {
                $orderBy[$field->getAttribute('name')] = XmlUtils::phpize($field->getAttribute('direction'));
            }
        }
        return $orderBy;
    }

    protected function parseImport(GeneratorCollection $collection, \DOMElement $node, string $path, string $file)
    {
        /** @var \DOMElement $resourceElement */
        if (!($resource = $node->getAttribute('resource') ?: null) && $resourceElement = $node->getElementsByTagName('resource')[0] ?? null) {
            $resource = [];
            /** @var \DOMAttr $attribute */
            foreach ($resourceElement->attributes as $attribute) {
                $resource[$attribute->name] = $attribute->value;
            }
        }

        if (!$resource) {
            throw new \InvalidArgumentException(sprintf('The <import> element in file "%s" must have a "resource" attribute or element.', $path));
        }
        $type = $node->getAttribute('type');

        $exclude = [];
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement && $child->localName === $exclude && self::NAMESPACE_URI === $child->namespaceURI) {
                $exclude[] = $child->nodeValue;
            }
        }

        if ($node->hasAttribute('exclude')) {
            if ($exclude) {
                throw new \InvalidArgumentException('You cannot use both the attribute "exclude" and <exclude> tags at the same time.');
            }
            $exclude = [$node->getAttribute('exclude')];
        }

        $this->setCurrentDir(\dirname($path));

        $imported = $this->import($resource, '' !== $type ? $type : null, false, $file, $exclude) ?: [];
        foreach ($imported as $subCollection) {
            $collection->addCollection($subCollection);
        }
    }

    protected function loadFile(string $file): \DOMDocument
    {
        return XmlUtils::loadFile($file, __DIR__.static::SCHEME_PATH);
    }

    public function supports(mixed $resource, string $type = null): bool
    {
        return \is_string($resource) && 'xml' === pathinfo($resource, \PATHINFO_EXTENSION) && (!$type || 'xml' === $type);
    }
}
