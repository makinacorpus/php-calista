<?php

namespace MakinaCorpus\Dashboard\Datasource;

/**
 * Sanitized version of an incomming query for a datasource
 */
class Query
{
    const LIMIT_DEFAULT = 24;
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';
    const URL_VALUE_SEP = '|';

    private $currentDisplay = '';
    private $filters = [];
    private $inputDefinition;
    private $limit = self::LIMIT_DEFAULT;
    private $page = 1;
    private $rawSearchString = '';
    private $route = '';
    private $routeParameters = [];
    private $sortField = '';
    private $sortOrder = self::SORT_DESC;

    /**
     * Default constructor
     *
     * @param InputDefinition $inputDefinition
     *   Current configuration
     * @param string $route
     *   Current route
     * @param string[] $routeParameters
     *   Route parameters (filters minus the default values)
     * @param string[] $filters
     *   Current filters (including defaults)
     */
    public function __construct(InputDefinition $inputDefinition, $route, array $filters = [], array $routeParameters = [])
    {
        $this->inputDefinition = $inputDefinition;
        $this->filters = $filters;
        $this->route = $route;
        $this->routeParameters = $routeParameters;

        $this->findCurrentDisplay();
        $this->findRange();
        $this->findSearch();
        $this->findSort();

        // Now for security, prevent anything that is not a filter from
        // existing into the filter array
        foreach (array_keys($this->filters) as $name) {
            if (!$inputDefinition->isFilterAllowed($name)) {
                unset($this->filters[$name]);
            }
        }
    }

    /**
     * Find range from query
     */
    private function findRange()
    {
        if (!$this->inputDefinition->isLimitAllowed()) {
            // Limit cannot be changed
            $this->limit = $this->inputDefinition->getDefaultLimit();
        } else {
            // Limit can be changed, we must find it from the parameters
            $limitParameter = $this->inputDefinition->getLimitParameter();
            if ($limitParameter && isset($this->routeParameters[$limitParameter])) {
                $this->limit = (int)$this->routeParameters[$limitParameter];
            }

            // Additional security, do not allow negative or 0 limit
            if ($this->limit <= 0) {
                $this->limit = $this->inputDefinition->getDefaultLimit();
            }
        }

        // Pager initialization, only if enabled
        if ($this->inputDefinition->isPagerEnabled()) {
            $pageParameter = $this->inputDefinition->getPagerParameter();
            if ($pageParameter && isset($this->routeParameters[$pageParameter])) {
                $this->page = (int)$this->routeParameters[$pageParameter];
            }

            // Additional security, do not allow negative or 0 page
            if ($this->page <= 0) {
                $this->page = 1;
            }
        }
    }

    /**
     * Find sort from query
     */
    private function findSort()
    {
        $sortFieldParameter = $this->inputDefinition->getSortFieldParameter();
        if ($sortFieldParameter && isset($this->routeParameters[$sortFieldParameter])) {
            $sortField = $this->routeParameters[$sortFieldParameter];
            if ($this->inputDefinition->isSortAllowed($sortField)) {
                $this->sortField = (string)$this->routeParameters[$sortFieldParameter];
            }
        }

        $sortOrderParameter = $this->inputDefinition->getSortOrderParameter();
        if ($sortOrderParameter && isset($this->routeParameters[$sortOrderParameter])) {
            $this->sortOrder = strtolower($this->routeParameters[$sortOrderParameter]) === self::SORT_DESC ? self::SORT_DESC : self::SORT_ASC;
        }
    }

    /**
     * Find search from query
     */
    private function findSearch()
    {
        if ($this->inputDefinition->isSearchEnabled()) {
            $searchParameter = $this->inputDefinition->getSearchParameter();
            if ($searchParameter && isset($this->routeParameters[$searchParameter])) {
                $this->rawSearchString = (string)$this->routeParameters[$searchParameter];
            }
        }
    }

    /**
     * Find current display from query
     */
    private function findCurrentDisplay()
    {
        $displayParameter = $this->inputDefinition->getDisplayParameter();
        if ($displayParameter && isset($this->routeParameters[$displayParameter])) {
            $this->currentDisplay = (string)$this->routeParameters[$displayParameter];
        }
    }

    /**
     * Get value from a filter, it might be an expanded array of values
     *
     * @param string $name
     * @param string $default
     *
     * @return string|string[]
     */
    public function get($name, $default = '')
    {
        return isset($this->filters[$name]) ? $this->filters[$name] : $default;
    }

    /**
     * Does the filter is set
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->filters);
    }

    /**
     * Get input definition
     *
     * @return InputDefinition
     */
    public function getInputDefinition()
    {
        return $this->inputDefinition;
    }

    /**
     * Get current display switch
     *
     * @return string
     */
    public function getCurrentDisplay()
    {
        return $this->currentDisplay;
    }

    /**
     * Is a sort field set
     *
     * @return bool
     */
    public function hasSortField()
    {
        return !!$this->sortField;
    }

    /**
     * Get sort field
     *
     * @return string
     */
    public function getSortField()
    {
        return $this->sortField;
    }

    /**
     * Get sort order
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Get limit
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Get offset
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->limit * max([0, $this->page - 1]);
    }

    /**
     * Get page number, starts with 1
     *
     * @return int
     */
    public function getPageNumber()
    {
        return $this->page;
    }

    /**
     * Get raw search string, even if search parsing is enabled
     *
     * @return string
     */
    public function getRawSearchString()
    {
        return $this->rawSearchString;
    }

    /**
     * Get the complete filter array
     *
     * @return array
     */
    public function all()
    {
        return $this->filters;
    }

    /**
     * Get current route
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Get the query without the parsed query string
     *
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }
}
