<?php

namespace MakinaCorpus\Dashboard\Datasource;

/**
 * Base implementation which leaves null a few mathods
 */
abstract class AbstractDatasource implements DatasourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilters(Query $query)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields(Query $query)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function init(Query $query)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function validateItems(Query $query, array $idList)
    {
        return false;
    }
}
