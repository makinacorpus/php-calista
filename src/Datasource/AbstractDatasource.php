<?php

namespace MakinaCorpus\Dashboard\Datasource;

use MakinaCorpus\Dashboard\Page\SortCollection;
use MakinaCorpus\Dashboard\Page\Filter;

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
    public function getAllowedFilters()
    {
        return array_map(
            function (Filter $filter) {
                return $filter->getField();
            },
            $this->getFilters()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return new SortCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedSorts()
    {
        return array_keys($this->getSorts()->getAllowedSorts());
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
