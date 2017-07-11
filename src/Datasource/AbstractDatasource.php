<?php

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Error\CalistaError;

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
    public function supportsStreaming()
    {
        return false; // Sensible default
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
     * Create an empty result
     *
     * @return DefaultDatasourceResult
     */
    protected function createEmptyResult()
    {
        return new DefaultDatasourceResult($this->getItemClass(), []);
    }

    /**
     * Create default result iterator with the provided information
     *
     * @param array|\Traversable $items
     * @param null|int $totalCount
     *
     * @return DefaultDatasourceResult
     */
    protected function createResult($items, $totalCount = null)
    {
        if (!is_array($items) && !$items instanceof \Traversable) {
            throw new CalistaError("given items are nor an array nor a \Traversable instance");
        }

        $result = new DefaultDatasourceResult($this->getItemClass(), $items);

        if (null !== $totalCount) {
            $result->setTotalItemCount($totalCount);
        }

        return $result;
    }
}
