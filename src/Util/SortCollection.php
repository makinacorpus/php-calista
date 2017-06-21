<?php

namespace MakinaCorpus\Dashboard\Util;

use MakinaCorpus\Dashboard\Datasource\Query;

/**
 * Sort manager, it worthes the shot to have a decicated class for this
 */
class SortCollection implements \Countable
{
    private $allowed = [];
    private $defaultField = null;
    private $defaultOrder = Query::SORT_DESC;
    private $links;

    /**
     * Default constructor
     *
     * @param string[] $allowed
     *   Keys are fields, values are labels
     * @param string $defaultField
     *   Default field, if none, first will be the default
     * @param string $defaultOrder
     *   Default order, if none, DESC will be the default
     */
    public function __construct(array $allowed = [], $defaultField = null, $defaultOrder = Query::SORT_DESC)
    {
        $this->allowed = $allowed;
        $this->defaultOrder = $defaultOrder;

        if ($defaultField) {
            $this->defaultField = $defaultField;
        } else if ($allowed) {
            $this->defaultField = key($allowed);
        }
    }

    /**
     * Get allowed sort field list
     *
     * @return string[]
     */
    public function getAllowedSorts()
    {
        return $this->allowed;
    }

    /**
     * Get current page sort field
     *
     * @param Query $query
     *
     * @return string
     */
    public function getCurrentFieldTitle(Query $query)
    {
        $field = $query->getSortField();

        if ($field && isset($this->allowed[$field])) {
            return $this->allowed[$field];
        }
    }

    /**
     * Get current page sort order title
     *
     * @param Query $query
     *
     * @return string
     */
    public function getCurrentOrderTitle(Query $query)
    {
        return $query->getSortOrder() === 'desc' ? "descending" : "ascending";
    }

    /**
     * Build link
     *
     * @return Link
     */
    private function buildLink($query, $route, $param, $value, $label, $current, $default)
    {
        if ($value === $default) {
            unset($query[$param]);
        } else {
            $query = [$param => $value] + $query;
        }

        return new Link($label, $route, $query, $value === $current);
    }

    /**
     * Get link for field
     *
     * @param string $field
     *
     * @return Link
     */
    public function getLink($field, Query $query)
    {
        if (!isset($this->allowed[$field])) {
            throw new \InvalidArgumentException(sprintf("%s: is not a valid search field", $field));
        }

        if (null === $this->links) {
            $this->getFieldLinks($query);
        }

        return $this->links[$field];
    }

    /**
     * Get sort field links
     *
     * @return Link[]
     */
    public function getFieldLinks(Query $query)
    {
        if (null !== $this->links) {
            return $this->links;
        }

        $this->links = [];

        $route    = $query->getRoute();
        $params   = $query->getRouteParameters();
        $current  = $query->getSortField();
        $param    = $query->getInputDefinition()->getSortFieldParameter();

        foreach ($this->allowed as $value => $label) {
            $this->links[] = $this->buildLink($params, $route, $param, $value, $label, $current, $this->defaultField);
        }

        return $this->links;
    }

    /**
     * Get sort order links
     *
     * @return Link[]
     */
    public function getOrderLinks(Query $query)
    {
        $ret      = [];

        $route    = $query->getRoute();
        $params   = $query->getRouteParameters();
        $current  = $query->getSortField();
        $param    = $query->getInputDefinition()->getSortOrderParameter();

        foreach ([
            Query::SORT_ASC   => "ascending",
            Query::SORT_DESC  => "descending",
        ] as $value => $label) {
            $ret[] = $this->buildLink($params, $route, $param, $value, $label, $current, $this->defaultOrder);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->allowed);
    }
}
