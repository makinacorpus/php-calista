<?php

namespace MakinaCorpus\Dashboard\Drupal\DependencyInjection\Compiler;

use MakinaCorpus\Dashboard\Drupal\Action\AbstractActionProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers action processors into the action registry
 */
class ActionProcessorRegisterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('udashboard.processor_registry')) {
            return;
        }
        $definition = $container->getDefinition('udashboard.processor_registry');

        // Register automatic action provider based on action processors
        $taggedServices = $container->findTaggedServiceIds('udashboard.action');
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            if (!$refClass->isSubclassOf(AbstractActionProcessor::class)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement extend "%s".', $id, AbstractActionProcessor::class));
            }

            $definition->addMethodCall('register', [new Reference($id)]);
        }
    }
}
