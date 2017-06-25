<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\DatasourceResultInterface;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\View\AbstractView;
use MakinaCorpus\Dashboard\View\ViewDefinition;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used to test the abstract view
 */
class DummyView extends AbstractView
{
    /**
     * Passthrougth for normalizeProperties().
     */
    public function normalizePropertiesPassthrought(ViewDefinition $viewDefinition, $class)
    {
        return $this->normalizeProperties($viewDefinition, $class);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query)
    {
        return new Response();
    }
}
