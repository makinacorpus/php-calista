<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\DatasourceResultInterface;

/**
 * @codeCoverageIgnore
 */
class PageResult
{
    private $route;
    private $state;
    private $items;
    private $filter;
    private $filters = [];
    private $visualFilters = [];
    private $sort;

    /**
     * Default constructor
     *
     * @param string $route
     * @param PageState $state
     * @param RequestFilter $filter
     * @param DatasourceResultInterface $items
     * @param Filter[] $filters
     * @param Filter[] $visualFilters
     * @param SortManager $sort
     */
    public function __construct(PageState $state, RequestFilter $filter, DatasourceResultInterface $items, array $filters = [], array $visualFilters = [], SortManager $sort = null)
    {
        $this->state = $state;
        $this->items = $items;
        $this->filter = $filter;
        $this->filters = $filters;
        $this->visualFilters = $visualFilters;
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->filter->getRoute();
    }

    /**
     * @return PageState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return DatasourceResultInterface
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return RequestFilter
     */
    public function getRequestFilter()
    {
        return $this->filter;
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return Filter[]
     */
    public function getVisualFilters()
    {
        return $this->visualFilters;
    }

    /**
     * @return SortManager
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Serialize page state
     *
     * @return mixed[]
     *   Suitable for JSON
     */
    public function queryToArray()
    {
        $query = $this->filter->getRouteParameters();

        foreach ($query as $index => $value) {
            if ($value === null || $value === '') {
                unset($query[$index]);
            }
        }

        return $query;
    }

    /**
     * Serialize page state
     *
     * @return string
     *   Suitable for JSON
     */
    public function queryToJson()
    {
        return json_encode($this->queryToArray());
    }
}
