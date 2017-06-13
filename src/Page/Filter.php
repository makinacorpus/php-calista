<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\Query;

/**
 * Default implementation that will convert a single hashmap to a set of links
 */
class Filter implements \Countable
{
    private $choicesMap = [];
    private $queryParameter;
    private $title;
    private $isSafe = false;

    /**
     * Default constructor
     *
     * @param string $queryParameter
     *   Query parameter name
     * @param string $title
     *   Filter title
     */
    public function __construct($queryParameter, $title = null)
    {
        $this->queryParameter = $queryParameter;
        $this->title = $title;
    }

    /**
     * Set choices map
     *
     * Choice map is a key-value array in which keys are indexed values and
     * values are human readable names that will supplant the indexed values
     * for end-user display, this has no effect on the query.
     *
     * @param string[] $choicesMap
     *   Keys are filter value, values are human readable labels
     *
     * @return Filter
     */
    public function setChoicesMap($choicesMap)
    {
        $this->isSafe = true;
        $this->choicesMap = $choicesMap;

        return $this;
    }

    /**
     * {inheritdoc}
     */
    public function getTitle()
    {
        if (!$this->title) {
            return $this->queryParameter;
        }

        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getField()
    {
        return $this->queryParameter;
    }

    /**
     * Get selected values from query
     *
     * @param string[] $query
     *
     * @return string[]
     */
    private function getSelectedValues($query)
    {
        $values = [];

        if (isset($query[$this->queryParameter])) {

            $values = $query[$this->queryParameter];

            if (!is_array($values)) {
                if (false !== strpos($values, Query::URL_VALUE_SEP)) {
                    $values = explode(Query::URL_VALUE_SEP, $values);
                } else {
                    $values = [$values];
                }
            }
        }

        return array_map('trim', $values);
    }

    /**
     * Get query parameters for a singe link
     *
     * @param string[] $query
     *   Contextual query that represents the current page state
     * @param string $value
     *   Value for the given link
     * @param boolean $remove
     *   Instead of adding the value, it must removed from the query
     *
     * @return string[]
     *   New query with value added or removed
     */
    private function getParametersForLink($query, $value, $remove = false)
    {
        if (isset($query[$this->queryParameter])) {
            if (is_array($query[$this->queryParameter])) {
                $actual = $query[$this->queryParameter];
            } else {
                $actual = explode(Query::URL_VALUE_SEP, $query[$this->queryParameter]);
            }
        } else {
            $actual = [];
        }

        if ($remove) {
            if (false !== ($pos = array_search($value, $actual))) {
                unset($actual[$pos]);
            }
        } else {
            if (false === array_search($value, $actual)) {
                $actual[] = $value;
            }
        }

        if (empty($actual)) {
            unset($query[$this->queryParameter]);
            return $query;
        } else {
            sort($actual);
            return [$this->queryParameter => implode(Query::URL_VALUE_SEP, $actual)] + $query;
        }
    }

    /**
     * Get links
     *
     * @param Query $query
     *
     * @return Link[]
     */
    public function getLinks(Query $query)
    {
        $ret = [];

        $route = $query->getRoute();
        $query = $query->getRouteParameters();

        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {

            $isActive = in_array($value, $selectedValues);

            if ($isActive) {
                $linkQuery = $this->getParametersForLink($query, $value, true);
            } else {
                $linkQuery = $this->getParametersForLink($query, $value);
            }

            $ret[] = new Link($label, $route, $linkQuery, $isActive);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->choicesMap);
    }

    /**
     * @return bool
     */
    public function isSafe()
    {
        return $this->isSafe;
    }

    /**
     * @return array
     */
    public function getChoicesMap()
    {
        return $this->choicesMap;
    }
}

