<?php

namespace MakinaCorpus\Calista\Datasource;

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
    public function validateItems(Query $query, array $idList)
    {
        return false;
    }

    /**
     * Create default result iterator with the provided information
     *
     * @param array $items
     * @param null|int $totalCount
     *
     * @return DefaultDatasourceResult
     */
    protected function createResult(array $items, $totalCount = null)
    {
        $result = new DefaultDatasourceResult($this->getItemClass(), $items);

        if (null !== $totalCount) {
            $result->setTotalItemCount($totalCount);
        }

        return $result;
    }
}
