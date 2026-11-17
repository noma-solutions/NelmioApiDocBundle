<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle\DependencyInjection\Compiler;

use Nelmio\ApiDocBundle\Util\MetadataModeResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Feeds MetadataModeResolver with doctrine.orm.mappings.*.type / prefix.
 *
 * When noma_solutions_api.metadata_mode_rules is present, those prefixes win
 * (same semantics as NomaSolutions ApiBundle MetadataModeRulesPass).
 *
 * @internal
 */
final class MetadataModeRulesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('nelmio_api_doc.metadata_mode_resolver')) {
            return;
        }

        $rules = [];
        foreach ($container->getExtensionConfig('doctrine') as $config) {
            if (!isset($config['orm']['mappings']) || !is_array($config['orm']['mappings'])) {
                continue;
            }

            foreach ($config['orm']['mappings'] as $mapping) {
                if (!is_array($mapping)) {
                    continue;
                }

                $prefix = $mapping['prefix'] ?? null;
                $type = $mapping['type'] ?? null;
                if (!is_string($prefix) || '' === $prefix) {
                    continue;
                }
                if (MetadataModeResolver::MODE_ATTRIBUTE !== $type && MetadataModeResolver::MODE_ANNOTATION !== $type) {
                    continue;
                }

                $rules[$prefix] = $type;
            }
        }

        if ($container->hasParameter('noma_solutions_api.metadata_mode_rules')) {
            $extra = $container->getParameter('noma_solutions_api.metadata_mode_rules');
            if (is_array($extra)) {
                foreach ($extra as $extraPrefix => $mode) {
                    if (!is_string($extraPrefix) || '' === $extraPrefix) {
                        continue;
                    }
                    $normalized = MetadataModeResolver::MODE_ATTRIBUTE === $mode
                        ? MetadataModeResolver::MODE_ATTRIBUTE
                        : MetadataModeResolver::MODE_ANNOTATION;

                    $rules[$extraPrefix] = $normalized;

                    foreach (array_keys($rules) as $existingPrefix) {
                        if ($existingPrefix === $extraPrefix) {
                            continue;
                        }
                        if (0 === strpos($existingPrefix, $extraPrefix.'\\')) {
                            $rules[$existingPrefix] = $normalized;
                        }
                    }
                }
            }
        }

        $container->getDefinition('nelmio_api_doc.metadata_mode_resolver')
            ->setArgument(0, $rules);
    }
}
