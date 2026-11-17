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

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Areas
{
    /** @var string[] */
    private $areas;

    /**
     * @param string[]|array{value?: string[]} $value Doctrine annotation uses ['value' => [...]]; PHP attribute uses a list
     */
    public function __construct(array $value = [])
    {
        if (array_key_exists('value', $value) && is_array($value['value'])) {
            $areasList = $value['value'];
        } else {
            $areasList = $value;
        }

        if (!is_array($areasList)) {
            throw new \InvalidArgumentException('An array of areas was expected');
        }

        $areas = [];
        foreach ($areasList as $area) {
            if (!is_string($area)) {
                throw new \InvalidArgumentException('An area must be given as a string');
            }

            if (!in_array($area, $areas, true)) {
                $areas[] = $area;
            }
        }

        if (0 === count($areas)) {
            throw new \LogicException('At least one area is expected');
        }

        $this->areas = $areas;
    }

    public function has(string $area): bool
    {
        return in_array($area, $this->areas, true);
    }
}
