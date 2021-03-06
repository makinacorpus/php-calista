<?php

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Calista\DependencyInjection\ServiceInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents a view, anything that can be displayed from datasource data
 */
interface ViewInterface extends ServiceInterface
{
    /**
     * Render the view
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration
     * @param DatasourceResultInterface $items
     *   Items from a datasource
     * @param Query $query
     *   Incoming query that was given to the datasource
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query);

    /**
     * Render the view as a response
     *
     * @param ViewDefinition $viewDefinition
     *   The view configuration
     * @param DatasourceResultInterface $items
     *   Items from a datasource
     * @param Query $query
     *   Incoming query that was given to the datasource
     *
     * @return Response
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query);
}
