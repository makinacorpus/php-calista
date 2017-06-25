<?php

namespace MakinaCorpus\Dashboard\DependencyInjection\Compiler;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\DependencyInjection\PageDefinitionInterface;
use MakinaCorpus\Dashboard\View\ViewInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers page definitions
 */
class PageDefinitionRegisterPass implements CompilerPassInterface
{
    /**
     * Register services with given tag, implementing the given tag, into the
     * main registry/factory of this module
     *
     * @param ContainerBuilder $container
     * @param string $tagName
     * @param string $registerMethod
     * @param string $serviceClass
     */
    private function registerServices(ContainerBuilder $container, $tagName, $registerMethod, $serviceClass)
    {
        // @codeCoverageIgnoreStart
        if (!$container->hasDefinition('udashboard.view_factory')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $definition = $container->getDefinition('udashboard.view_factory');

        $types = $classes = [];

        // Register custom action providers
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $attributes) {
            $def = $container->getDefinition($id);

            $class = $container->getParameterBag()->resolveValue($def->getClass());
            $refClass = new \ReflectionClass($class);

            // @codeCoverageIgnoreStart
            if (!$refClass->implementsInterface($serviceClass)) {
                throw new \InvalidArgumentException(sprintf('Service "%s" must implement interface "%s".', $id, $serviceClass));
            }
            // @codeCoverageIgnoreEnd

            if (empty($attributes[0]['id'])) {
                $typeId = $def->getClass();
            } else {
                $typeId = $attributes[0]['id'];
            }

            $def->setShared(false);
            $types[$typeId] = $id;
            $classes[$class] = $id;
        }

        if ($types) {
            $definition->addMethodCall($registerMethod, [$types, $classes]);
        }

    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerServices($container, 'udashboard.page_definition', 'registerPageDefinitions', PageDefinitionInterface::class);
        $this->registerServices($container, 'udashboard.view', 'registerViews', ViewInterface::class);
        $this->registerServices($container, 'udashboard.datasource', 'registerDatasources', DatasourceInterface::class);
    }
}
