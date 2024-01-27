<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\DependencyInjection\Compiler;

use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SerializerPass implements CompilerPassInterface
{
    /**
     * Replaces the available metadata drivers (yaml, xml, and annotation)
     * with our metadata driver, as we do not use any of those. There's
     * currently no other way that I can find to get our driver into the
     * chain in front of the rest.
     */
    public function process(ContainerBuilder $container): void
    {
<<<<<<< HEAD
        if ($container->hasDefinition('jms_serializer.metadata.doctrine_type_driver')) {
            $definition = $container->getDefinition('jms_serializer.metadata.doctrine_type_driver');
            $definition->replaceArgument(0, new Reference(ApiMetadataDriver::class));
=======
        if ($container->hasDefinition('jms_serializer.metadata.annotation_or_attribute_driver')) {
            $definition = $container->getDefinition('jms_serializer.metadata.annotation_or_attribute_driver');
            $definition->setClass(\Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver::class);
>>>>>>> c898f59bc8 (bump jms/serializer-bundle dependency and ensure compatibility)
        }
    }
}
