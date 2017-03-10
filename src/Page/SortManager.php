<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sort manager, it worthes the shot to have a decicated class for this
 *
 * All constants are compatible with Drupal DBTNG sorts.
 */
class SortManager implements \Countable
{
    use StringTranslationTrait;
    use PrepareableTrait;

    /**
     * Descending order
     */
    const DESC = 'desc';

    /**
     * Ascending order
     */
    const ASC = 'asc';

    /**
     * @var string
     */
    private $paramField = 'st';

    /**
     * @var string
     */
    private $paramOrder = 'by';

    /**
     * @var string
     */
    private $defaultField = null;

    /**
     * @var string
     */
    private $defaultOrder = self::DESC;

    /**
     * @var string[]
     */
    private $allowed = [];

    /**
     * @var Link[]
     */
    private $links;

    /**
     * Default constructor
     *
     * @param string $paramField
     *   Sort field query parameter name
     * @param string $paramOrder
     *   Sort order query parameter name
     */
    public function __construct($paramField = null, $paramOrder = null)
    {
        if ($paramField) {
            $this->paramField = $paramField;
        }
        if ($paramOrder) {
            $this->paramOrder = $paramOrder;
        }
    }

    /**
     * Get sort field parameter name
     *
     * @return string
     */
    public function getFieldParameter()
    {
        return $this->paramField;
    }

    /**
     * Get sort order parameter name
     *
     * @return string
     */
    public function getOrderParameter()
    {
        return $this->paramOrder;
    }

    /**
     * Set default sort
     *
     * @param string $field
     * @param string $order
     */
    public function setDefault($field, $order = self::DESC)
    {
        if (!isset($this->allowed[$field])) {
            trigger_error(sprintf("%s field is not allowed for sorting", $field), E_USER_ERROR);
            return;
        }
        if (!in_array($order, [self::ASC, self::DESC])) {
            trigger_error(sprintf("%s order is not allowed for sorting", $order), E_USER_ERROR);
            return;
        }

        $this->defaultField = $field;
        $this->defaultOrder = $order;
        $this->links = null;
    }

    /**
     * Set available fields
     *
     * @param string[] $allowed
     *   Keys are field names values are human readable names
     */
    public function setFields(array $allowed)
    {
        $this->allowed = $allowed;

        if (!$this->defaultField) {
            $this->defaultField = key($this->allowed);
        }

        $this->links = null;
    }

    /**
     * Get current page sort field
     *
     * @param string[] $query
     *
     * @return string
     */
    public function getCurrentField($query)
    {
        if (isset($query[$this->paramField])) {
            $field = $query[$this->paramField];
            if ($this->allowed[$field]) {
                return $field;
            }
        }

        return $this->defaultField;
    }

    /**
     * Get current page sort field
     *
     * @param string[] $query
     *
     * @return string
     */
    public function getCurrentFieldTitle($query)
    {
        $field = $this->getCurrentField($query);

        if ($field) {
            return $this->allowed[$field];
        }
    }

    /**
     * Get current page sort order
     *
     * @param string[] $query
     *
     * @return string
     */
    public function getCurrentOrder($query)
    {
        if (isset($query[$this->paramOrder])) {
            $order = $query[$this->paramOrder];
            if (in_array($order, [self::ASC, self::DESC])) {
                return $order;
            }
        }
        return $this->defaultOrder;
    }

    /**
     * Get current page sort order title
     *
     * @param string[] $query
     *
     * @return string
     */
    public function getCurrentOrderTitle($query)
    {
        return $this->getCurrentOrder($query) === 'desc' ? t("descending") : t("ascending");
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
    public function getLink($field)
    {
        if (!isset($this->allowed[$field])) {
            throw new \InvalidArgumentException(sprintf("%s: is not a valid search field", $field));
        }

        if (null === $this->links) {
            $this->getFieldLinks();
        }

        return $this->links[$field];
    }

    /**
     * Get sort field links
     *
     * @return Link[]
     */
    public function getFieldLinks()
    {
        if (null !== $this->links) {
            return $this->links;
        }

        $this->links = [];

        $route = $this->getRoute();
        $query = $this->getRouteParamaters();

        $current = $this->getCurrentField($query);

        foreach ($this->allowed as $value => $label) {
            $this->links[$value] = $this->buildLink($query, $route, $this->paramField, $value, $label, $current, $this->defaultField);
        }

        return $this->links;
    }

    /**
     * Get sort order links
     *
     * @return Link[]
     */
    public function getOrderLinks()
    {
        $ret = [];

        $route = $this->getRoute();
        $query = $this->getRouteParamaters();

        $current = $this->getCurrentOrder($query);
        $map = [self::ASC => $this->t("ascending"), self::DESC => $this->t("descending")];

        foreach ($map as $value => $label) {
            $ret[$value] = $this->buildLink($query, $route, $this->paramOrder, $value, $label, $current, $this->defaultOrder);
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
