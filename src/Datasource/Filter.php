<?php

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Util\Link;

/**
 * Default implementation that will convert a single hashmap to a set of links
 */
class Filter implements \Countable
{
    private $choicesMap = [];
    private $queryParameter;
    private $arbitraryInput = false;
    private $title;
    private $mandatory = false;
    private $isSafe = false;
    private $noneOption;
    private $multiple = true;

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
     * Set or unset the "multiple" flag, default is true
     *
     * @param bool $toggle
     *
     * @return self
     */
    public function setMultiple($toggle = true)
    {
        $this->multiple = (bool)$toggle;

        return $this;
    }

    /**
     * Does this filter allows multiple input
     *
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * Set the "None/All/N/A" option
     *
     * @param string
     *
     * @return self
     */
    public function setNoneOption($value)
    {
        $this->noneOption = (string)$value;

        return $this;
    }

    /**
     * Get the none option
     *
     * @return string
     */
    public function getNoneOption()
    {
        return $this->noneOption;
    }

    /**
     * Set or unset the mandatory flag
     *
     * @param bool $toggle
     *
     * @return self
     */
    public function setMandatory($toggle = true)
    {
        $this->mandatory = (bool)$toggle;

        return $this;
    }

    /**
     * Is this filter mandatory
     *
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Is arbitrary input field
     *
     * @return bool
     */
    public function isArbitraryInput()
    {
        return !$this->choicesMap && $this->arbitraryInput;
    }

    /**
     * Set or unset the arbitrary input flag
     *
     * @param bool $toggle
     *
     * @return self
     */
    public function setArbitraryInput($toggle = true)
    {
        $this->arbitraryInput = (bool)$toggle;

        return $this;
    }

    /**
     * Has this filter choices
     *
     * @returnn bool
     */
    public function hasChoices()
    {
        return !empty($this->choicesMap);
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
     * Remove selected choices
     *
     * @param array $choices
     */
    public function removeChoices(array $choices)
    {
        $this->choicesMap = array_diff_key($this->choicesMap, array_flip($choices));
    }

    /**
     * Remove selected choices
     *
     * @param array $choices
     */
    public function removeChoicesNotIn(array $choices)
    {
        $this->choicesMap = array_intersect_key($this->choicesMap, array_flip($choices));
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

