<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\DatasourceResultInterface;
use MakinaCorpus\Dashboard\Datasource\Query;

/**
 * @codeCoverageIgnore
 */
class PageResult
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Query
     */
    private $query;

    /**
     * @var DatasourceResultInterface
     */
    private $items;

    /**
     * @var Filter[]
     */
    private $enabledFilters;

    /**
     * @var Filter[]
     */
    private $enabledVisualFilters;

    /**
     * Default constructor
     *
     * @param Configuration $configuration
     * @param Query $query
     * @param DatasourceResultInterface $items
     * @param Filter[] $filters
     * @param Filter[] $visualFilters
     */
    public function __construct(Configuration $configuration, Query $query, DatasourceResultInterface $items, array $enabledFilters = [], array $enabledVisualFilters = [])
    {
        $this->configuration = $configuration;
        $this->query = $query;
        $this->items = $items;
        $this->enabledFilters = $enabledFilters;
        $this->enabledVisualFilters = $enabledVisualFilters;
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return DatasourceResultInterface
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->enabledFilters;
    }

    /**
     * @return Filter[]
     */
    public function getVisualFilters()
    {
        return $this->enabledVisualFilters;
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
