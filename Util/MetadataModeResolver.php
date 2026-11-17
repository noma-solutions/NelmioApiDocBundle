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

/**
 * Resolves preferred annotation vs attribute metadata mode from Doctrine ORM mapping prefixes.
 *
 * Preference is exclusive per prefix; MappingAwareAnnotationReader still merges both
 * sources for Nelmio/Swagger metadata so residual @SWG docblocks keep working.
 */
final class MetadataModeResolver
{
    public const MODE_ANNOTATION = 'annotation';
    public const MODE_ATTRIBUTE = 'attribute';

    /** @var array<string, string> */
    private $mappingModes;

    /** @var string */
    private $defaultMode;

    /**
     * @param array<string, string> $mappingModes prefix => annotation|attribute
     */
    public function __construct(array $mappingModes = [], string $defaultMode = self::MODE_ANNOTATION)
    {
        $this->mappingModes = $mappingModes;
        $this->defaultMode = self::MODE_ATTRIBUTE === $defaultMode
            ? self::MODE_ATTRIBUTE
            : self::MODE_ANNOTATION;
        uksort($this->mappingModes, static function (string $a, string $b): int {
            return strlen($b) <=> strlen($a);
        });
    }

    public function resolve(string $className): string
    {
        foreach ($this->mappingModes as $prefix => $mode) {
            if ($className === $prefix || 0 === strpos($className, $prefix.'\\')) {
                return self::MODE_ATTRIBUTE === $mode
                    ? self::MODE_ATTRIBUTE
                    : self::MODE_ANNOTATION;
            }
        }

        return $this->defaultMode;
    }

    public function isAttribute(string $className): bool
    {
        return self::MODE_ATTRIBUTE === $this->resolve($className);
    }
}
