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

use Swagger\Annotations\Operation as BaseOperation;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Operation extends BaseOperation
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct($data = [])
    {
        if (!is_array($data)) {
            $data = [];
        }

        parent::__construct($data);
    }
}
