<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageDefinitionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests page definition and page builder factory
 */
class FooPageDefinition implements PageDefinitionInterface
{
    /**
     * {@inheritdoc}
     */
    public function createConfiguration(array $options = [])
    {
        return new Configuration(array_merge($options, [
            'limit_allowed' => true,
            'limit_param'   => '_limit',
            'pager_enable'  => true,
            'pager_param'   => '_page',
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Configuration $configuration, Request $request)
    {
        $builder
            ->setConfiguration($configuration)
            ->setDatasource(new IntArrayDatasource())
            ->setAllowedTemplates([
                'default' => 'module:udashboard:views/Page/page.html.twig',
            ])
            ->setDefaultDisplay('default')
        ;
    }
}
