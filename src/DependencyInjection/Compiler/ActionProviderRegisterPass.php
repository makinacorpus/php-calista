<?php

namespace MakinaCorpus\Calista\DependencyInjection\Compiler;

use MakinaCorpus\Calista\Action\ActionProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers action providers into the action registry
 */
class ActionProviderRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('calista.action_provider_registry')) {
            return;
        }
        $definition = $container->getDefinition('calista.action_provider_registry');
        // @codeCoverageIgnoreEnd

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds('calista.action_provider');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if (!$refClass->implementsInterface(ActionProviderInterface::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, ActionProviderInterface::class));
            }
            // @codeCoverageIgnoreEnd

            $definition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
