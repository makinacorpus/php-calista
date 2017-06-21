<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\AbstractDatasource;
use MakinaCorpus\Dashboard\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Dashboard\Datasource\Query;

/**
 * Empty datasource
 */
class EmptyDatasource extends AbstractDatasource
{
    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return \stdClass::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        return new DefaultDatasourceResult([]);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
}
