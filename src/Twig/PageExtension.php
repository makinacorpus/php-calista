<?php

namespace MakinaCorpus\Dashboard\Twig;

use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\Filter;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Display pages, build views, gives us helpers for it
 *
 *   {{ udashboard_page(view) }}
 *
 * Which would be equivalent to:
 *
 *   {{ view.createView(app.request).render() }}
 */
class PageExtension extends \Twig_Extension
{
    /**
     * Display when rendering is not possible
     */
    const RENDER_NOT_POSSIBLE = '<em>N/A</em>';

    private $debug = false;
    private $propertyAccess;
    private $propertyInfo;
    private $requestStack;

    /**
     * Default constructor
     *
     * @param RequestStack $requestStack
     * @param PropertyAccessor $propertyAccess
     * @param PropertyInfoExtractorInterface $propertyInfo
     * @param bool $debug
     */
    public function __construct(RequestStack $requestStack, PropertyAccessor $propertyAccess, PropertyInfoExtractorInterface $propertyInfo, $debug = false)
    {
        $this->propertyAccess = $propertyAccess;
        $this->propertyInfo = $propertyInfo;
        $this->requestStack = $requestStack;
        $this->debug = $debug;
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
            if ($this->debug) {
                throw new PropertyTypeError("Class '%s' does not exists", $class);
            }
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
     * Render an integer value
     */
    private function renderInt($value, array $options = [])
    {
        return number_format($value, 0, '.', $options['thousand_separator']);
    }

    /**
     * Render a float value
     */
    private function renderFloat($value, array $options = [])
    {
        return number_format($value, $options['decimal_precision'], $options['decimal_separator'], $options['thousand_separator']);
    }

    /**
     * Render a boolean value
     */
    private function renderBool($value, array $options = [])
    {
        if ($options['bool_as_int']) {
            return $value ? "1" : "0";
        }

        if ($value) {
            if ($options['bool_value_true']) {
                return $options['bool_value_true'];
            }

            return "true"; // @todo translate

        } else {
            if ($options['bool_value_false']) {
                return $options['bool_value_false'];
            }

            return "false"; // @todo translate
        }
    }

    /**
     * Render a string value
     */
    private function renderString($value, array $options = [])
    {
        $value = strip_tags($value);

        if (strlen($value) > $options['string_maxlength']) {
            $value = substr($value, 0, $options['string_maxlength']);

            if ($options['string_ellipsis']) {
                if (is_string($options['string_ellipsis'])) {
                    $value .= $options['string_ellipsis'];
                } else {
                    $value .= '...';
                }
            }
        }

        return $value;
    }

    /**
     * Render a single value
     */
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

    /**
     * Render a collection of values
     */
    private function renderValueCollection(Type $type, $values, array $options = [])
    {
        if (!$values instanceof \Traversable && !is_array($values)) {
            if ($this->debug) {
                throw new PropertyTypeError("Collection value is not a \Traversable nor an array");
            }
            return self::RENDER_NOT_POSSIBLE;
        }

        $ret = [];
        foreach ($values as $value) {
            $ret[] = $this->renderSingleValue($type->getCollectionValueType(), $value, $options);
        }

        return implode($options['collection_separator'], $ret);
    }

    /**
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string $propery
     *   Property name
     * @param mixed[] $options
     *   Display options for the property
     *
     * @return string
     */
    public function renderItemProperty($item, $property, array $options = [])
    {
        if (!is_object($item)) {
            if ($this->debug) {
                throw new PropertyTypeError(sprintf("Item is not an object %s found instead while rendering the '%s' property", gettype($item), $property));
            }
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

        // @todo dynamize this
        //   for example:
        //      - options in a specific yml file?
        //      - options in parameters?
        //      - global options per type, where?
        //      - options per class and type, where?
        //      - options per class and propery, where?
        $options = [
            'bool_as_int'           => false,
            'bool_value_false'      => "Non",
            'bool_value_true'       => "Oui",
            'collection_separator'  => ', ',
            'decimal_precision'     => 2,
            'decimal_separator'     => ',',
            'string_ellipsis'       => true,
            'string_maxlength'      => 3,
            'thousand_separator'    => '&nbsp;',
        ];

        // @todo would there be a way to handle mixed types (more than one type)?
        // OK just take the very first, mixed types is a bad idea overall
        foreach ($types as $type) {

            $builtin = $type->getBuiltinType();
            $class = $type->getClassName();

            if ($type->isCollection() || Type::BUILTIN_TYPE_ARRAY === $builtin) {
                $values = $this->propertyAccess->getValue($item, $property);

                return $this->renderValueCollection($type, $values, $options);
            }

            if (Type::BUILTIN_TYPE_OBJECT === $builtin) {
                // @todo allow per class specific/custom/user-driven
                //   implementation for display
                return self::RENDER_NOT_POSSIBLE;
            }

            $value = $this->propertyAccess->getValue($item, $property);

            return $this->renderSingleValue($type, $value, $options);
        }
    }

    /**
     * Render page builder
     *
     * @param TwigView $view
     *
     * @return string
     *   Rendered page
     */
    public function renderPage(TwigView $view)
    {
        return $view->createView($this->requestStack->getCurrentRequest())->render();
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
     * @param Filter[] $filters
     *
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
     * @param Filter[] $filters
     * @param string[] $query
     *
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
