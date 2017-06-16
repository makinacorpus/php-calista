<?php

namespace MakinaCorpus\Dashboard\Twig;

use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Display pages, considering that 'page' is a variable that points to a
 * PageBuilder instance that was properly setup:
 *
 *   {{ udashboard_page(page) }}
 *
 * Which would be equivalent to:
 *
 *   {{ page.searchAndRender(app.request) }}
 */
class PageExtension extends \Twig_Extension
{
    /**
     * Display when rendering is not possible
     */
    const RENDER_NOT_POSSIBLE = '<em>N/A</em>';

    private $propertyAccess;
    private $propertyInfo;
    private $requestStack;

    /**
     * Default constructor
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack, PropertyAccessor $propertyAccess, PropertyInfoExtractorInterface $propertyInfo)
    {
        $this->propertyAccess = $propertyAccess;
        $this->propertyInfo = $propertyInfo;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('udashboard_page', [$this, 'renderPage'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('udashboard_item_definition', [$this, 'getItemDefinition'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('udashboard_item_property', [$this, 'renderItemProperty'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('udashboardFilterDefinition', [$this, 'filterDefinition'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('udashboardFilterQuery', [$this, 'filterQuery'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('udashboard_query_param', [$this, 'flattenQueryParam']),
        ];
    }

    /**
     * Get item definition
     *
     * @param string $class
     *   Must be a string, and an existing class
     *
     * @return string[]
     *   Each key is a property name, each value is a short description for it
     */
    public function getItemDefinition($class)
    {
        $ret = [];

        if (!$class || !class_exists($class)) {
            // @todo Raise exception?
            return $ret;
        }

        foreach ($this->propertyInfo->getProperties($class) as $property) {
            $description = $this->propertyInfo->getShortDescription($class, $property);
            if ($description) {
                $ret[$property] = $description;
            } else {
                $ret[$property] = $property;
            }
        }

        return $ret;
    }

    /**
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string $propery
     *   Property name
     *
     * @return string
     */
    public function renderItemProperty($item, $property)
    {
        if (!is_object($item)) {
            // @todo Raise exception?
            return self::RENDER_NOT_POSSIBLE;
        }

        $class = get_class($item);

        if (!$this->propertyInfo->isReadable($class, $property)) {
            return self::RENDER_NOT_POSSIBLE;
        }

        $types = $this->propertyInfo->getTypes($class, $property);
        if (!$types) {
            return self::RENDER_NOT_POSSIBLE;
        }

        return "got something...";
    }

    /**
     * Render page builder
     *
     * @param PageBuilder $pageBuilder
     *
     * @return string
     *   Rendered page
     */
    public function renderPageBuilder(PageBuilder $pageBuilder)
    {
        return $pageBuilder->searchAndRender($this->requestStack->getCurrentRequest());
    }

    /**
     * Render page builder
     *
     * @param PageView $pageView
     *
     * @return string
     *   Rendered page
     */
    public function renderPageView(PageView $pageView)
    {
        return $pageView->render();
    }

    /**
     * Render page builder
     *
     * @param PageBuilder|PageView $page
     *
     * @return string
     *   Rendered page
     */
    public function renderPage($page)
    {
        if ($page instanceof PageBuilder) {
            return $this->renderPageBuilder($page);
        } else if ($page instanceof PageView) {
            return $this->renderPageView($page);
        } else {
            return '';
        }
    }

    /**
     * Flatten query param if array
     *
     * @param string|string[] $value
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
     * @param \MakinaCorpus\Dashboard\Page\Filter[] $filters
     * @return string
     */
    public function filterDefinition($filters)
    {
        $definition = [];

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
     * @param \MakinaCorpus\Dashboard\Page\Filter[] $filters
     * @param string[] $query
     * @return string
     */
    public function filterQuery($filters, $query)
    {
        $filterQuery = [];

        foreach ($filters as $filter) {
            if (isset($query[$filter->getField()])) {
                $filterQuery[$filter->getField()] = $query[$filter->getField()];
            }
        }

        return json_encode($filterQuery);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'udashboard_page';
    }
}
