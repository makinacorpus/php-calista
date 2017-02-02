<?php

namespace MakinaCorpus\Drupal\Dashboard\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class PortletRegisterPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('udashboard.portlet_registry')) {
            return;
        }

        $definition = $container->getDefinition('udashboard.portlet_registry');

        $taggedServices = $container->findTaggedServiceIds('udashboard.portlet');


        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addPortlet', [new Reference($id), $id]);
        }
    }
}
