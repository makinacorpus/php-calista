<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

/**
 * @codeCoverageIgnore
 */
class PageResult
{
    private $route;
    private $state;
    private $items;
    private $query;
    private $filters = [];
    private $visualFilters = [];
    private $sort;

    /**
     * Default constructor
     *
     * @param string $route
     * @param PageState $state
     * @param mixed[] $items
     * @param string[] $query
     * @param Filter[] $filters
     * @param Filter[] $visualFilters
     * @param SortManager $sort
     */
    public function __construct($route, PageState $state, PageQuery $query, array $items, array $filters = [], array $visualFilters = [], SortManager $sort = null)
    {
        $this->route = $route;
        $this->state = $state;
        $this->items = $items;
        $this->query = $query;
        $this->filters = $filters;
        $this->visualFilters = $visualFilters;
        $this->sort = $sort;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return PageState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return \mixed[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return PageQuery
     */
    public function getQuery()
    {
        return $this->query;
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
        $query = $this->query->getRouteParameters();

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
