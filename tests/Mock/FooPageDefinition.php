<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Datasource\InputDefinition;
use MakinaCorpus\Calista\DependencyInjection\AbstractPageDefinition;
use MakinaCorpus\Calista\View\Html\TwigView;
use MakinaCorpus\Calista\View\ViewDefinition;

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
    public function getViewDefinition(array $options = [])
    {
        return new ViewDefinition($options + [
            'default_display' => 'default',
            'templates' => [
                'default' => '@calista/page/page.html.twig',
            ],
            'view_type' => TwigView::class,
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
