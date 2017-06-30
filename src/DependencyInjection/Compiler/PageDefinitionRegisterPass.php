<?php

namespace MakinaCorpus\Calista\DependencyInjection\Compiler;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\DependencyInjection\DynamicPageDefinition;
use MakinaCorpus\Calista\DependencyInjection\PageDefinitionInterface;
use MakinaCorpus\Calista\View\ViewInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
        if (!$container->hasDefinition('calista.view_factory')) {
            return;
        }
        // @codeCoverageIgnoreEnd
        $definition = $container->getDefinition('calista.view_factory');

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
            if ($refClass->isSubclassOf(DynamicPageDefinition::class)) {
                $def->addMethodCall('setAnnotationReader', [new Reference('annotation_reader')]);
            }
            // @codeCoverageIgnoreEnd

            if (empty($attributes[0]['id'])) {
                $typeId = $def->getClass();
            } else {
                $typeId = $attributes[0]['id'];
            }

            $def->setShared(false);
            $def->setPublic(true);

            $types[$typeId] = $id;
            $classes[$class][] = $id;
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
        $this->registerServices($container, 'calista.page_definition', 'registerPageDefinitions', PageDefinitionInterface::class);
        $this->registerServices($container, 'calista.view', 'registerViews', ViewInterface::class);
        $this->registerServices($container, 'calista.datasource', 'registerDatasources', DatasourceInterface::class);
    }
}
