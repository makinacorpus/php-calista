<?php

namespace MakinaCorpus\Dashboard\DependencyInjection\Compiler;

use MakinaCorpus\Dashboard\Page\PageDefinitionInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers page definitions
 */
class PageDefinitionRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('udashboard.page_builder_factory')) {
            return;
        }
        $definition = $container->getDefinition('udashboard.page_builder_factory');

        $types = [];

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('udashboard.page_definition');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            if (!$refClass->implementsInterface(PageDefinitionInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, PageDefinitionInterface::class));
            }

            if (empty($attributes[0]['id'])) {
                $typeId = $def->getClass();
            } else {
                $typeId = $attributes[0]['id'];
            }

            $def->setShared(false);
            $types[$typeId] = $id;
        }

        if ($types) {
            $definition->addMethodCall('registerPageDefinitions', [$types]);
        }
    }
}
