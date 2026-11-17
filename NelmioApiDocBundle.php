<?php

/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Nelmio\ApiDocBundle;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Nelmio\ApiDocBundle\DependencyInjection\Compiler\ConfigurationPass;
use Nelmio\ApiDocBundle\DependencyInjection\Compiler\MetadataModeRulesPass;
use Nelmio\ApiDocBundle\DependencyInjection\Compiler\TagDescribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NelmioApiDocBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new TagDescribersPass());
        $container->addCompilerPass(new MetadataModeRulesPass());
    }

    /**
     * swagger-php registers a whitelist AnnotationRegistry loader that blocks
     * Composer autoload for non-Swagger annotations (e.g. JMS Serializer).
     * Register class_exists so JMS/Symfony annotations still resolve during /api/doc.
     */
    public function boot(): void
    {
        AnnotationRegistry::registerUniqueLoader('class_exists');
    }
}
