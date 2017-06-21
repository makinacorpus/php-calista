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
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('udashboard.view_factory')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $definition = $container->getDefinition('udashboard.view_factory');

        $types = [];

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('udashboard.view');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if (!$refClass->implementsInterface(PageDefinitionInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, PageDefinitionInterface::class));
            }
            // @codeCoverageIgnoreEnd

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
