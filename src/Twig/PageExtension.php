<?php

namespace MakinaCorpus\Calista\Twig;

use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Display pages, build views, gives us helpers for it
 */
class PageExtension extends \Twig_Extension
{
    private $debug = false;
    private $requestStack;
    private $propertyRenderer;

    /**
     * Default constructor
     *
     * @param RequestStack $requestStack
     * @param PropertyRenderer $propertyRenderer
     */
    public function __construct(RequestStack $requestStack, PropertyRenderer $propertyRenderer)
    {
        $this->requestStack = $requestStack;
        $this->propertyRenderer = $propertyRenderer;
    }

    /**
     * Enable or disable debug mode
     *
     * Mostly useful for unit tests
     *
     * @param string $debug
     */
    public function setDebug($debug = true)
    {
        $this->debug = (bool)$debug;
        $this->propertyRenderer->setDebug($debug);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('calista_item_property', [$this, 'renderItemProperty'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('calista_filter_definition', [$this, 'getFilterDefinition'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('calista_filter_query', [$this, 'getFilterQuery'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('calista_query_param', [$this, 'flattenQueryParam']),
        ];
    }

    /**
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string|PropertyView $propery
     *   Property name
     * @param mixed[] $options
     *   Display options for the property, dropped if the $property parameter
     *   is an instance of PropertyView
     *
     * @return string
     */
    public function renderItemProperty($item, $property, array $options = [])
    {
        return $this->propertyRenderer->renderItemProperty($item, $property, $options);
    }

    /**
     * Flatten query param if array
     *
     * @param string|string[] $value
     *
     * @codeCoverageIgnore
     */
    public function flattenQueryParam($value)
    {
        if (is_array($value)) {
            return implode(Query::URL_VALUE_SEP, $value);
        }

        return $value;
    }

    /**
     * Return a JSON encoded representing the filter definition
     *
     * @param Filter[] $filters
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getFilterDefinition($filters)
    {
        $definition = [];

        /** @var \MakinaCorpus\Calista\Datasource\Filter $filter */
        foreach ($filters as $filter) {
            $definition[] = [
                'value'   => $filter->getField(),
                'label'   => $filter->getTitle(),
                'options' => !$filter->isSafe() ?: $filter->getChoicesMap(),
            ];
        }

        return json_encode($definition);
    }

    /**
     * Return a JSON encoded representing the initial filter query
     *
     * @param Filter[] $filters
     * @param string[] $query
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getFilterQuery($filters, $query)
    {
        $filterQuery = [];

        /** @var \MakinaCorpus\Calista\Datasource\Filter $filter */
        foreach ($filters as $filter) {
            $field = $filter->getField();
            if (isset($query[$field])) {
                $filterQuery[$field] = $query[$field];
            }
        }

        return json_encode($filterQuery);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_page';
    }
}
