<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Calista configuration structure
 */
class CalistaConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('calista');

        // This is a very concise representation of pages, because it will
        // be validated at runtime using the OptionResolver component; we
        // only describe the required/possible keys and that's pretty much
        // it.
        $rootNode
            ->children()
                ->arrayNode('context_pane')
                    ->info('Context pane configuration')
                    ->canBeEnabled()
                ->end()
                ->arrayNode('pages')
                    ->normalizeKeys(true)
                    ->prototype('array')
                        ->children()
                            ->variableNode('extra')->end()
                            ->variableNode('input')->end()
                            ->variableNode('view')->isRequired()->end()
                            ->scalarNode('datasource')->isRequired()->end()
                            ->scalarNode('id')->end()
                        ->end() // children
                    ->end() // prototype
                ->end() // pages
            ->end() // children
        ;

        return $treeBuilder;
    }
}
