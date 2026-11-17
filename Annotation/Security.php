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
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Security extends AbstractAnnotation
{
    /** {@inheritdoc} */
    public static $_types = [
        'name' => 'string',
    ];

    public static $_required = ['name'];

    /**
     * @var string
     */
    public $name;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct($data = [], ?string $name = null)
    {
        if (!is_array($data)) {
            $data = [];
        }

        parent::__construct($data);

        if (null !== $name) {
            $this->name = $name;
        }
    }
}
