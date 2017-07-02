<?php

namespace MakinaCorpus\Calista\Datasource;

/**
 * Virtual datasource allows you to use queries without datasources
 *
 * @todo this not very elegant, sorry
 */
class VirtualDatasource extends AbstractDatasource
{
    /**
     * {@inheritdoc}
     */
    public function supportsPagination()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        return $this->createEmptyResult();
    }
}
