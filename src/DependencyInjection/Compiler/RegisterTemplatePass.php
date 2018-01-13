<?php

namespace MakinaCorpus\Calista\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers our own custom templates when using dependency injection.
 *
 * @codeCoverageIgnore
 *
 * @deprecated
 *   Use the bundle namespace instead
 */
class RegisterTemplatePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $id = 'twig.loader';

        if ($container->hasAlias($id)) {
            $id = (string)$container->getAlias($id);
        }

        if ($container->hasDefinition($id)) {
            $definition = $container->getDefinition($id);
            $definition->addMethodCall('addPath', [dirname(dirname(__DIR__)) . '/Resources/views', 'calista']);
            $definition->addMethodCall('addPath', [dirname(dirname(__DIR__)) . '/Resources/views', 'Calista']);
        }
    }
}
