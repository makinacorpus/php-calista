<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\DependencyInjection\AbstractPageDefinition;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use MakinaCorpus\Dashboard\View\ViewDefinition;

/**
 * Tests page definition and page definition factory
 */
class FooPageDefinition extends AbstractPageDefinition
{
    private $datasource;

    public function __construct()
    {
        $this->datasource = new IntArrayDatasource();
    }

    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = [])
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
    public function getViewDefinition()
    {
        return new ViewDefinition([
            'default_display' => 'default',
            'templates' => [
                'default' => 'module:udashboard:views/Page/page.html.twig',
            ],
            'view_type' => 'twig_page',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatasource()
    {
        return $this->datasource;
    }
}
