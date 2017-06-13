<?php

namespace MakinaCorpus\Dashboard\Datasource;

use MakinaCorpus\Dashboard\Page\SortCollection;

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
    public function getSorts(Query $query)
    {
        return new SortCollection();
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
