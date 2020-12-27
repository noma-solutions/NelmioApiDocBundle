<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Model;

use EXSyst\Component\Swagger\Schema;
use EXSyst\Component\Swagger\Swagger;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Symfony\Component\PropertyInfo\Type;

final class ModelRegistry
{
    private $alternativeNames = [];

    private $unregistered = [];

    private $models = [];

    private $names = [];

    private $modelDescribers = [];

    private $api;

    /**
     * @param ModelDescriberInterface[]|iterable $modelDescribers
     *
     * @internal
     */
    public function __construct($modelDescribers, Swagger $api, array $alternativeNames = [])
    {
        $this->modelDescribers = $modelDescribers;
        $this->api = $api;
        $this->alternativeNames = []; // last rule wins

        foreach (array_reverse($alternativeNames) as $alternativeName => $criteria) {
            $this->alternativeNames[] = $model = new Model(
                new Type('object', false, $criteria['type']),
                $criteria['groups']
            );
            $this->names[$model->getHash()] = $alternativeName;
            $this->api->getDefinitions()->get($alternativeName);
        }
    }

    public function register(Model $model, string $alternativeName = null): string
    {
        $hash = $model->getHash();
        if (!isset($this->models[$hash])) {
            $this->models[$hash] = $model;
            $this->unregistered[] = $hash;
        }
        if (isset($this->names[$hash]) && $alternativeName !== null && $this->names[$hash] !== $alternativeName) {
            throw new \LogicException(
                'Provided alternative name: '.$alternativeName.' that is different than one already known for this model: '.$this->names[$hash]
            );
        }
        if (!isset($this->names[$hash])) {
            $this->names[$hash] = $alternativeName ?? $this->generateModelName($model);
        }

        // Reserve the name
        $this->api->getDefinitions()->get($this->names[$hash]);

        return '#/definitions/'.$this->names[$hash];
    }

    /**
     * @internal
     */
    public function registerDefinitions()
    {
        while (count($this->unregistered)) {
            $tmp = [];
            foreach ($this->unregistered as $hash) {
                $tmp[$this->names[$hash]] = $this->models[$hash];
            }
            $this->unregistered = [];

            foreach ($tmp as $name => $model) {
                $schema = null;
                foreach ($this->modelDescribers as $modelDescriber) {
                    if ($modelDescriber instanceof ModelRegistryAwareInterface) {
                        $modelDescriber->setModelRegistry($this);
                    }
                    if ($modelDescriber->supports($model)) {
                        $schema = new Schema();
                        $modelDescriber->describe($model, $schema);

                        break;
                    }
                }

                if (null === $schema) {
                    throw new \LogicException(
                        sprintf(
                            'Schema of type "%s" can\'t be generated, no describer supports it.',
                            $this->typeToString($model->getType())
                        )
                    );
                }

                $this->api->getDefinitions()->set($name, $schema);
            }
        }

        if (empty($this->unregistered) && !empty($this->alternativeNames)) {
            foreach ($this->alternativeNames as $model) {
                $this->register($model);
            }
            $this->alternativeNames = [];
            $this->registerDefinitions();
        }

        $this->removeDuplicates($this->api);
    }

    private function removeDuplicates(Swagger $api)
    {
        $array = $api->toArray();

        $search = true;

        $i = 0;

        while ($search !== false || $i++ > 100) {
            $hashes = [];
            $definitionUses = [];
            $pathUses = [];
            $duplicates = [];

            foreach ($array['definitions'] ?? [] as $modelName => $definition) {
                foreach ($definition['properties'] ?? [] as $propertyName => $property) {
                    $ref = $property['$ref'] ?? null;
                    $itemsRef = $property['items']['$ref'] ?? null;

                    if ($ref) {
                        $refModelName = str_replace('#/definitions/', '', $ref);
                        if (!isset($definitionUses[$refModelName])) {
                            $definitionUses[$refModelName] = [];
                        }
                        $definitionUses[$refModelName][] = [$modelName, $propertyName, '$ref'];
                    } elseif ($itemsRef) {
                        $refModelName = str_replace('#/definitions/', '', $itemsRef);
                        if (!isset($definitionUses[$refModelName])) {
                            $definitionUses[$refModelName] = [];
                        }
                        $definitionUses[$refModelName][] = [$modelName, $propertyName, 'items'];
                    }
                }

                $hash = md5(json_encode($definition));
                if (isset($hashes[$hash])) {
                    foreach ($hashes[$hash] as $baseName => $definition) {
                        if ($this->sameModelNameBase($baseName, $modelName) === false) {
                            continue;
                        }

                        if (!isset($duplicates[$baseName])) {
                            $duplicates[$baseName] = [];
                        }

                        $duplicates[$baseName][] = $modelName;
                    }
                } else {
                    if (!isset($hashes[$hash])) {
                        $hashes[$hash] = [];
                    }

                    $hashes[$hash][$modelName] = $definition;
                }
            }

            if (count($duplicates) === 0) {
                $search = false;
            }

            foreach ($array['paths'] ?? [] as $pathName => $pathMethods) {
                foreach ($pathMethods as $methodName => $methodData) {
                    foreach ($methodData['responses'] as $code => $responseData) {
                        $schemaRef = $responseData['schema']['$ref'] ?? null;
                        $modelName = str_replace('#/definitions/', '', $schemaRef);
                        if (!isset($pathUses[$modelName])) {
                            $pathUses[$modelName] = [];
                        }
                        $pathUses[$modelName][] = [$pathName, $methodName, $code];
                    }
                }
            }

            foreach ($duplicates as $baseModelName => $duplicatedNames) {
                foreach ($duplicatedNames as $duplicatedName) {
                    if (isset($definitionUses[$duplicatedName])) {
                        foreach ($definitionUses[$duplicatedName] as $usedIn) {
                            list($usedInModelName, $usedInProperty, $usedInType) = $usedIn;
                            if ($usedInType === 'items') {
                                $array['definitions'][$usedInModelName]['properties'][$usedInProperty]['items']['$ref'] = '#/definitions/'.$baseModelName;
                            } else {
                                $array['definitions'][$usedInModelName]['properties'][$usedInProperty]['$ref'] = '#/definitions/'.$baseModelName;
                            }
                        }
                    }

                    if (isset($pathUses[$duplicatedName])) {
                        foreach ($pathUses[$duplicatedName] as $pathUseData) {
                            list($pathName, $methodName, $code) = $pathUseData;
                            $array['paths'][$pathName][$methodName]['responses'][$code]['schema']['$ref'] = '#/definitions/'.$baseModelName;
                        }
                    }

                    unset($array['definitions'][$duplicatedName]);
                    $this->api->getDefinitions()->remove($duplicatedName);
                }
            }
        }


        $this->api->merge($array);
    }

    private function sameModelNameBase(string $baseName, string $modelName)
    {
        $search = [];
        $replace = [];

        for ($i = 0; $i < 10; $i++) {
            $search[] = (string)$i;
            $replace[] = '';
        }

        return
            str_replace($search, $replace, $baseName) ===
            str_replace($search, $replace, $modelName);
    }

    private function generateModelName(Model $model): string
    {
        $definitions = $this->api->getDefinitions();

        $name = $base = $this->getTypeShortName($model->getType());

        $i = 1;
        while ($definitions->has($name)) {
            ++$i;
            $name = $base.$i;
        }

        return $name;
    }

    private function getTypeShortName(Type $type): string
    {
        if (null !== $type->getCollectionValueType()) {
            return $this->getTypeShortName($type->getCollectionValueType()).'[]';
        }

        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()) {
            $parts = explode('\\', $type->getClassName());

            return end($parts);
        }

        return $type->getBuiltinType();
    }

    private function typeToString(Type $type): string
    {
        if (Type::BUILTIN_TYPE_OBJECT === $type->getBuiltinType()) {
            return $type->getClassName();
        } elseif ($type->isCollection()) {
            if (null !== $type->getCollectionValueType()) {
                return $this->typeToString($type->getCollectionValueType()).'[]';
            } else {
                return 'mixed[]';
            }
        } else {
            return $type->getBuiltinType();
        }
    }
}
