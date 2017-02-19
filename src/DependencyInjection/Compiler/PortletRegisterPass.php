<?php

namespace MakinaCorpus\Drupal\Dashboard\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use MakinaCorpus\Drupal\Dashboard\Portlet\AbstractPortlet;

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
            $portletDefinition = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($portletDefinition->getClass());
            if (is_subclass_of($class, AbstractPortlet::class)) {
                $portletDefinition->addMethodCall('setPageBuilder', [new Reference('udashboard.empty_page_builder')]);
                $portletDefinition->addMethodCall('setAccount', [new Reference('current_user')]);
            }

            $definition->addMethodCall('addPortlet', [new Reference($id), $id]);
        }
    }
}
