<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\AbstractDatasource;
use MakinaCorpus\Dashboard\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Dashboard\Datasource\Filter;
use MakinaCorpus\Dashboard\Datasource\Query;

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

    public function getFilters()
    {
        return array_map(
            function ($name) {
                return new Filter($name);
            },
            $this->allowedFilters
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return array_combine($this->allowedSorts, $this->allowedSorts);
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
