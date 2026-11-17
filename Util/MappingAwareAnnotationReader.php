<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Util;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Swagger\Annotations\AbstractAnnotation;

/**
 * Doctrine Reader that prefers attributes or annotations per MetadataModeResolver,
 * but always merges the other source.
 *
 * Attribute reading is limited to Nelmio/Swagger so unrelated PHP attributes
 * (e.g. Routing) do not leak into API doc consumers. Doctrine annotation reading
 * stays unfiltered so FOSRest/Assert (and other) docblocks keep working.
 *
 * Preference only affects merge order (what wins in get*Annotation).
 */
final class MappingAwareAnnotationReader implements Reader
{
    /** @var Reader */
    private $annotationReader;

    /** @var MetadataModeResolver */
    private $modeResolver;

    public function __construct(Reader $annotationReader, MetadataModeResolver $modeResolver)
    {
        $this->annotationReader = $annotationReader;
        $this->modeResolver = $modeResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        return $this->mergeAnnotations(
            $this->readAttributes($class),
            $this->annotationReader->getClassAnnotations($class),
            $this->modeResolver->isAttribute($class->getName())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        foreach ($this->getClassAnnotations($class) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        $className = $method->getDeclaringClass()->getName();

        return $this->mergeAnnotations(
            $this->readAttributes($method),
            $this->annotationReader->getMethodAnnotations($method),
            $this->modeResolver->isAttribute($className)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        foreach ($this->getMethodAnnotations($method) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $className = $property->getDeclaringClass()->getName();

        return $this->mergeAnnotations(
            $this->readAttributes($property),
            $this->annotationReader->getPropertyAnnotations($property),
            $this->modeResolver->isAttribute($className)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        foreach ($this->getPropertyAnnotations($property) as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @param object[] $attributes
     * @param object[] $annotations
     *
     * @return object[]
     */
    private function mergeAnnotations(array $attributes, array $annotations, bool $attributePreferred): array
    {
        if ($attributePreferred) {
            return array_values(array_merge($attributes, $annotations));
        }

        return array_values(array_merge($annotations, $attributes));
    }

    /**
     * @param \ReflectionClass|\ReflectionMethod|\ReflectionProperty $reflector
     *
     * @return object[]
     */
    private function readAttributes($reflector): array
    {
        if (!method_exists($reflector, 'getAttributes')) {
            return [];
        }

        $annotations = [];
        foreach ($reflector->getAttributes() as $attribute) {
            $name = $attribute->getName();
            if (!$this->isSupportedAttribute($name)) {
                continue;
            }

            try {
                $annotations[] = $attribute->newInstance();
            } catch (\Throwable $e) {
                // Skip attributes that cannot be instantiated in this context.
            }
        }

        return $annotations;
    }

    private function isSupportedAttribute(string $className): bool
    {
        if (0 === strpos($className, 'Nelmio\\ApiDocBundle\\Annotation\\')) {
            return true;
        }

        if (0 === strpos($className, 'Swagger\\Annotations\\')) {
            return true;
        }

        if (is_a($className, AbstractAnnotation::class, true)) {
            return true;
        }

        return Areas::class === $className;
    }
}
