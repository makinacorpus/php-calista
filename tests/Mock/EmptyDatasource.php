<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\AbstractDatasource;
use MakinaCorpus\Dashboard\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\SortCollection;

/**
 * Empty datasource
 */
class EmptyDatasource extends AbstractDatasource
{
    private $allowedFilters = [];
    private $allowedSorts = [];

    /**
     * Default constructor
     *
     * @param string[] $allowedFilters
     * @param string[] $allowedSorts
     */
    public function __construct(array $allowedFilters = [], array $allowedSorts = [])
    {
        $this->allowedFilters = $allowedFilters;
        $this->allowedSorts = $allowedSorts;
    }

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
    public function getAllowedFilters()
    {
        return $this->allowedFilters;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return new SortCollection($this->allowedSorts);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedSorts()
    {
        return $this->getSorts()->getAllowedSorts();
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
