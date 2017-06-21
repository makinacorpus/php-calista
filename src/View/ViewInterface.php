<?php

namespace MakinaCorpus\Dashboard\View;

use Symfony\Component\HttpFoundation\Response;
use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\Query;

/**
 * Represents a view, anything that can be displayed from datasource data
 */
interface ViewInterface
{
    /**
     * Set view definition
     *
     * @param ViewDefinition $viewDefinition
     */
    public function setViewDefinition(ViewDefinition $viewDefinition);

    /**
     * Render the view
     *
     * @return string
     */
    public function render(DatasourceInterface $datasource, Query $query);

    /**
     * Render the view as a response
     *
     * @return Response
     */
    public function renderAsResponse(DatasourceInterface $datasource, Query $query);
}
