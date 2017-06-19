<?php

namespace MakinaCorpus\Dashboard\Twig;

use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

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
            new \Twig_SimpleFilter('udashboard_filter_definition', [$this, 'getfilterDefinition'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('udashboard_filter_query', [$this, 'getFilterQuery'], ['is_safe' => ['html']]),
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

    private function renderInt($value, array $options = [])
    {
        // @todo thousand separator as options
        return 'INT';
    }

    private function renderFloat($value, array $options = [])
    {
        // @todo decimals as options, thousand separator as options, round as option
        return 'FLOAT';
    }

    private function renderBool($value, array $options = [])
    {
        // @todo labels as options, else use translator
        return 'BOOL';
    }

    private function renderString($value, array $options = [])
    {
        // @todo summary size as option
        return 'STRING';
    }

    private function renderSingleValue(Type $type, $value, array $options = [])
    {
        switch ($type->getBuiltinType()) {

            case Type::BUILTIN_TYPE_INT:
                return $this->renderInt($value, $options);

            case Type::BUILTIN_TYPE_FLOAT:
                return $this->renderFloat($value, $options);

            case Type::BUILTIN_TYPE_STRING:
                return $this->renderString($value, $options);

            case Type::BUILTIN_TYPE_BOOL:
                return $this->renderBool($value, $options);

            case Type::BUILTIN_TYPE_NULL:
                return '';

            default:
                return self::RENDER_NOT_POSSIBLE;
        }
    }

    private function renderValueCollection(Type $type, $values, array $options = [])
    {
        if (!$values instanceof \Traversable && !is_array($values)) {
            return self::RENDER_NOT_POSSIBLE;
        }

        // @todo iterate and render
        // @todo separator as option
        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->renderSingleValue($type, $value, $options);
        }

        return implode(', ', $ret);
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

        // @todo would there be a way to handle mixed types (more than one type)?
        // OK just take the very first, mixed types is a bad idea overall
        foreach ($types as $type) {

            $builtin = $type->getBuiltinType();
            $class = $type->getClassName();

            if ($type->isCollection() || Type::BUILTIN_TYPE_ARRAY === $builtin) {
                // @todo use propertyaccessor to fetch value
                return $this->renderValueCollection($type, null);
            }

            if (Type::BUILTIN_TYPE_OBJECT === $builtin) {
                // @todo allow per class specific/custom/user-driven
                //   implementation for display
                return self::RENDER_NOT_POSSIBLE;
            }

            // @todo use propertyaccessor to fetch value
            return $this->renderSingleValue($type, null);
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
    public function getfilterDefinition($filters)
    {
        $definition = [];

        /** @var \MakinaCorpus\Dashboard\Page\Filter $filter */
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
    public function getFilterQuery($filters, $query)
    {
        $filterQuery = [];

        /** @var \MakinaCorpus\Dashboard\Page\Filter $filter */
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
        return 'udashboard_page';
    }
}
