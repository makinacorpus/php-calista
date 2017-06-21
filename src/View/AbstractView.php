<?php

namespace MakinaCorpus\Dashboard\View;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\DatasourceResultInterface;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Error\ConfigurationError;

/**
 * Represents a view, anything that can be displayed from datasource data
 */
abstract class AbstractView implements ViewInterface
{
    protected $viewDefinition;

    /**
     * {@inheritdoc}
     */
    public function setViewDefinition(ViewDefinition $viewDefinition)
    {
        if ($this->viewDefinition) {
            throw new ConfigurationError("you are overriding an already set view definition");
        }

        $this->viewDefinition = $viewDefinition;
    }

    /**
     * Execute query with datasource
     *
     * @param DatasourceInterface $datasource
     * @param Query $query
     *
     * @return DatasourceResultInterface
     */
    protected function execute(DatasourceInterface $datasource, Query $query)
    {
        $datasource->init($query);

        return $datasource->getItems($query);
    }
}
