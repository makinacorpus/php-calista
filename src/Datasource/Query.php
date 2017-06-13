<?php

namespace MakinaCorpus\Dashboard\Datasource;

/**
 * Parses and cleanups the incomming query from a Symfony request
 */
class Query
{
    const LIMIT_DEFAULT = 24;
    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';
    const URL_VALUE_SEP = '|';

    private $configuration;
    private $currentDisplay = '';
    private $filters = [];
    private $limit = self::LIMIT_DEFAULT;
    private $page = 1;
    private $rawSearchString = '';
    private $route = '';
    private $routeParameters = [];
    private $sortField = '';
    private $sortOrder = self::SORT_DESC;
    private $baseQuery = [];

    /**
     * Default constructor
     *
     * @param Configuration $configuration
     *   Current configuration
     * @param string $route
     *   Current route
     * @param string[] $routeParameters
     *   Route parameters (filters minus the default values)
     * @param string[] $filters
     *   Current filters (including defaults)
     */
    public function __construct(Configuration $configuration, $route, array $filters = [], array $routeParameters = [], array $baseQuery = [])
    {
        $this->baseQuery = $baseQuery;
        $this->configuration = $configuration;
        $this->filters = $filters;
        $this->route = $route;
        $this->routeParameters = $routeParameters;

        $this->findCurrentDisplay();
        $this->findRange();
        $this->findSearch();
        $this->findSort();
    }

    /**
     * Find range from query
     */
    private function findRange()
    {
        if (!$this->configuration->isLimitAllowed()) {
            // Limit cannot be changed
            $this->limit = $this->configuration->getDefaultLimit();
        } else {
            // Limit can be changed, we must find it from the parameters
            $limitParameter = $this->configuration->getLimitParameter();
            if ($limitParameter && isset($this->routeParameters[$limitParameter])) {
                $this->limit = (int)$this->routeParameters[$limitParameter];
            }

            // Additional security, do not allow negative or 0 limit
            if ($this->limit <= 0) {
                $this->limit = $this->configuration->getDefaultLimit();
            }
        }

        // Pager initialization, only if enabled
        if ($this->configuration->isPagerEnabled()) {
            $pageParameter = $this->configuration->getPagerParameter();
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
        $sortFieldParameter = $this->configuration->getSortFieldParameter();
        if ($sortFieldParameter && isset($this->routeParameters[$sortFieldParameter])) {
            $this->sortField = (string)$this->routeParameters[$sortFieldParameter];
        }

        $sortOrderParameter = $this->configuration->getSortOrderParameter();
        if ($sortOrderParameter && isset($this->routeParameters[$sortOrderParameter])) {
            $this->sortOrder = strtolower($this->routeParameters[$sortOrderParameter]) === self::SORT_DESC ? self::SORT_DESC : self::SORT_ASC;
        }
    }

    /**
     * Find search from query
     */
    private function findSearch()
    {
        if ($this->configuration->isSearchEnabled()) {
            $searchParameter = $this->configuration->getSearchParameter();
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
        $displayParameter = $this->configuration->getDisplayParameter();
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
     * Get base query
     *
     * @return array
     */
    public function getBaseQuery()
    {
        return $this->baseQuery;
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
    public function getAll()
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
