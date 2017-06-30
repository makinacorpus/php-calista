<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Usable extension for both Symfony, Drupal and may be other dependency
 * injection based environments.
 */
class CalistaExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);

        // From the configured pages, build services
        foreach ($configs as $config) {
            // Do not process everything at once, it will erase array keys
            // of all pages definitions except those from the very first config
            // file, and break all our services identifiers
            $config = $this->processConfiguration($configuration, [$config]);

            if (isset($config['pages'])) {
                foreach ($config['pages'] as $id => $array) {

                    // Determine both service and page identifier
                    $serviceId = 'calista.config_page.' . $id;
                    $pageId = empty($array['id']) ? $id : $array['id'];

                    $definition = new Definition();
                    $definition->setClass(ConfigPageDefinition::class);
                    $definition->setArguments([$array]);
                    // It needs to be true for the factory to be able to proceed
                    // with lazy loading.
                    $definition->setPublic(true);
                    $definition->addTag('calista.page_definition', ['id' => $pageId]);

                    $container->addDefinitions([$serviceId => $definition]);
                }
            }
        }

        $loader = new YamlFileLoader($container, new FileLocator(dirname(dirname(__DIR__)).'/config'));
        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new CalistaConfiguration();
    }
}
