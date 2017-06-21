<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageDefinitionInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests page definition and page builder factory
 */
class FooPageDefinition implements PageDefinitionInterface
{
    private $datasource;

    public function __construct()
    {
        $this->datasource = new IntArrayDatasource();
    }

    /**
     * {@inheritdoc}
     */
    public function createInputDefinition(array $options = [])
    {
        return new InputDefinition($this->datasource, array_merge($options, [
            'limit_allowed' => true,
            'limit_param'   => '_limit',
            'pager_enable'  => true,
            'pager_param'   => '_page',
        ]));
    }

    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, InputDefinition $inputDefinition, Request $request)
    {
        $builder
            ->setInputDefinition($inputDefinition)
            ->setDatasource($this->datasource)
            ->setAllowedTemplates([
                'default' => 'module:udashboard:views/Page/page.html.twig',
            ])
            ->setDefaultDisplay('default')
        ;
    }
}
