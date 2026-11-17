<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Tests\Util;

use Doctrine\Common\Annotations\Reader;
use Nelmio\ApiDocBundle\Annotation\Areas;
use Nelmio\ApiDocBundle\Util\MappingAwareAnnotationReader;
use Nelmio\ApiDocBundle\Util\MetadataModeResolver;
use PHPUnit\Framework\TestCase;

class MappingAwareAnnotationReaderTest extends TestCase
{
    public function testAnnotationModeKeepsNonSwaggerDocblockAnnotations(): void
    {
        $queryParam = new \stdClass();
        $queryParam->name = 'foo';

        $reader = $this->createReader(
            [$queryParam],
            new MetadataModeResolver([], MetadataModeResolver::MODE_ANNOTATION)
        );

        $method = new \ReflectionMethod(MappingAwareAnnotationReaderFixtures::class, 'withQueryParamDocblock');
        $annotations = $reader->getMethodAnnotations($method);

        $this->assertContains($queryParam, $annotations);
    }

    public function testAttributePreferredModeStillKeepsNonSwaggerDocblockAnnotations(): void
    {
        $queryParam = new \stdClass();
        $queryParam->name = 'foo';

        $reader = $this->createReader(
            [$queryParam],
            new MetadataModeResolver([
                'Nelmio\\ApiDocBundle\\Tests\\Util' => MetadataModeResolver::MODE_ATTRIBUTE,
            ])
        );

        $method = new \ReflectionMethod(MappingAwareAnnotationReaderFixtures::class, 'withQueryParamDocblock');
        $annotations = $reader->getMethodAnnotations($method);

        $this->assertContains($queryParam, $annotations);
    }

    public function testAssertLikeDocblockAnnotationsAreNotFiltered(): void
    {
        $notBlank = new \stdClass();
        $notBlank->constraint = 'NotBlank';

        $inner = $this->createMock(Reader::class);
        $inner->method('getMethodAnnotations')->willReturn([]);
        $inner->method('getClassAnnotations')->willReturn([]);
        $inner->method('getPropertyAnnotations')->willReturn([$notBlank]);

        $reader = new MappingAwareAnnotationReader(
            $inner,
            new MetadataModeResolver([], MetadataModeResolver::MODE_ANNOTATION)
        );

        $property = new \ReflectionProperty(MappingAwareAnnotationReaderFixtures::class, 'assertedProperty');
        $annotations = $reader->getPropertyAnnotations($property);

        $this->assertContains($notBlank, $annotations);
    }

    public function testAreasAttributeIsReadWithoutDocblock(): void
    {
        $reader = $this->createReader(
            [],
            new MetadataModeResolver([
                'Nelmio\\ApiDocBundle\\Tests\\Util' => MetadataModeResolver::MODE_ATTRIBUTE,
            ])
        );

        $method = new \ReflectionMethod(MappingAwareAnnotationReaderFixtures::class, 'withAreasAttribute');
        $areas = $reader->getMethodAnnotation($method, Areas::class);

        $this->assertInstanceOf(Areas::class, $areas);
        $this->assertTrue($areas->has('foo'));
    }

    public function testAttributePreferredStillMergesInnerDocblockAnnotations(): void
    {
        $swgLike = new \stdClass();
        $swgLike->annotation = 'SWG\\Get';

        $reader = $this->createReader(
            [$swgLike],
            new MetadataModeResolver([
                'Nelmio\\ApiDocBundle\\Tests\\Util' => MetadataModeResolver::MODE_ATTRIBUTE,
            ])
        );

        $method = new \ReflectionMethod(MappingAwareAnnotationReaderFixtures::class, 'withAreasAttribute');
        $annotations = $reader->getMethodAnnotations($method);

        $this->assertTrue(
            $this->containsInstanceOf($annotations, Areas::class),
            'Expected Areas attribute in merge result.'
        );
        $this->assertContains(
            $swgLike,
            $annotations,
            'Expected docblock annotations from the inner reader to remain available when attributes are preferred.'
        );
    }

    public function testUnsupportedAttributesAreNotRead(): void
    {
        $reader = $this->createReader(
            [],
            new MetadataModeResolver([
                'Nelmio\\ApiDocBundle\\Tests\\Util' => MetadataModeResolver::MODE_ATTRIBUTE,
            ])
        );

        $method = new \ReflectionMethod(MappingAwareAnnotationReaderFixtures::class, 'withUnsupportedAttribute');
        $annotations = $reader->getMethodAnnotations($method);

        $this->assertFalse(
            $this->containsInstanceOf($annotations, MappingAwareUnsupportedAttribute::class),
            'Unsupported PHP attributes must not leak through the reader.'
        );
        $this->assertTrue($this->containsInstanceOf($annotations, Areas::class));
    }

    public function testAttributePreferredReturnsAttributeFirstForSameType(): void
    {
        $docblockAreas = new Areas(['from-docblock']);

        $reader = $this->createReader(
            [$docblockAreas],
            new MetadataModeResolver([
                'Nelmio\\ApiDocBundle\\Tests\\Util' => MetadataModeResolver::MODE_ATTRIBUTE,
            ])
        );

        $method = new \ReflectionMethod(MappingAwareAnnotationReaderFixtures::class, 'withAreasAttribute');
        $areas = $reader->getMethodAnnotation($method, Areas::class);

        $this->assertInstanceOf(Areas::class, $areas);
        $this->assertTrue($areas->has('foo'));
        $this->assertFalse($areas->has('from-docblock'));
    }

    /**
     * @param object[] $methodAnnotations
     */
    private function createReader(array $methodAnnotations, MetadataModeResolver $modeResolver): MappingAwareAnnotationReader
    {
        $inner = $this->createMock(Reader::class);
        $inner->method('getMethodAnnotations')->willReturn($methodAnnotations);
        $inner->method('getClassAnnotations')->willReturn([]);
        $inner->method('getPropertyAnnotations')->willReturn([]);

        return new MappingAwareAnnotationReader($inner, $modeResolver);
    }

    /**
     * @param object[] $annotations
     */
    private function containsInstanceOf(array $annotations, string $className): bool
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $className) {
                return true;
            }
        }

        return false;
    }
}

#[\Attribute(\Attribute::TARGET_METHOD)]
final class MappingAwareUnsupportedAttribute
{
}

final class MappingAwareAnnotationReaderFixtures
{
    public $assertedProperty;

    public function withQueryParamDocblock(): void
    {
    }

    #[Areas(['foo'])]
    public function withAreasAttribute(): void
    {
    }

    #[Areas(['foo'])]
    #[MappingAwareUnsupportedAttribute]
    public function withUnsupportedAttribute(): void
    {
    }
}
