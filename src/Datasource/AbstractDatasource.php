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
    public function getItemClass()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPagination()
    {
        return true; // Sensible default
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return false; // Sensible default
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
