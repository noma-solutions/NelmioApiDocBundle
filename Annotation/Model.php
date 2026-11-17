<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\Annotation;

use Swagger\Annotations\AbstractAnnotation;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER | \Attribute::IS_REPEATABLE)]
final class Model extends AbstractAnnotation
{
    /** {@inheritdoc} */
    public static $_types = [
        'type' => 'string',
        'groups' => '[string]',
        'options' => '[mixed]',
    ];

    public static $_required = ['type'];

    public static $_parents = [
        'Swagger\Annotations\Parameter',
        'Swagger\Annotations\Response',
    ];

    /**
     * @var string
     */
    public $type;

    /**
     * @var string[]
     */
    public $groups;

    /**
     * @var mixed[]
     */
    public $options;

    /**
     * @param array<string, mixed> $data
     * @param string[]|null        $groups
     * @param mixed[]|null         $options
     */
    public function __construct(
        $data = [],
        ?string $type = null,
        ?array $groups = null,
        ?array $options = null
    ) {
        if (!is_array($data)) {
            $data = [];
        }

        parent::__construct($data);

        if (null !== $type) {
            $this->type = $type;
        }
        if (null !== $groups) {
            $this->groups = $groups;
        }
        if (null !== $options) {
            $this->options = $options;
        }
    }
}
