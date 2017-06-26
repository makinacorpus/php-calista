<?php

namespace MakinaCorpus\Dashboard\Twig;

use MakinaCorpus\Dashboard\Datasource\Filter;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Error\ConfigurationError;
use MakinaCorpus\Dashboard\Util\TypeUtil;
use MakinaCorpus\Dashboard\View\PropertyView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

/**
 * Display pages, build views, gives us helpers for it
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
     * Enable or disable debug mode
     *
     * Mostly useful for unit tests
     *
     * @param string $debug
     */
    public function setDebug($debug = true)
    {
        $this->debug = (bool)$debug;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
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

        if (0 < $options['string_maxlength'] && strlen($value) > $options['string_maxlength']) {
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
    private function renderValue(Type $type, $value, array $options = [])
    {
        if ($type->isCollection()) {
            return $this->renderValueCollection($type, $value, $options);
        }

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
            $ret[] = $this->renderValue($type->getCollectionValueType(), $value, $options);
        }

        return implode($options['collection_separator'], $ret);
    }

    /**
     * Get value
     *
     * @param object $item
     * @param string $property
     *
     * @return null|mixed
     *   Null if not found
     */
    private function getValue($item, $property)
    {
        try {
            return $this->propertyAccess->getValue($item, $property);

        } catch (NoSuchPropertyException $e) {
            if ($this->debug) {
                throw $e;
            }

            return null;
        }
    }

    /**
     * Render property for object
     *
     * @param object $item
     *   Item on which to find the property
     * @param PropertyView $propery
     *   Property view
     *
     * @return string
     */
    private function renderProperty($item, PropertyView $propertyView)
    {
        $options = $propertyView->getOptions();
        $property = $propertyView->getName();
        $value = null;

        if (is_object($item)) {
            $itemType = get_class($item);
        } else {
            $itemType = gettype($item);
        }

        // Skip property info if options contain a callback.
        if (isset($options['callback'])) {
            if (!is_callable($options['callback'])) {
                if ($this->debug) {
                    throw new ConfigurationError("callback '%s' for property '%s' on class '%s' is not callable", $options['callable'], $itemType, $property);
                }

                return self::RENDER_NOT_POSSIBLE;
            }

            if (!$propertyView->isVirtual()) {
                $value = $this->getValue($item, $property);
            }

            return call_user_func($options['callback'], $value, $options, $item);
        }

        // A virtual property with no callback should not be displayable at all
        if ($propertyView->isVirtual()) {
            if ($this->debug) {
                throw new ConfigurationError(sprintf("property '%s' on class '%s' is virtual but has no callback", $property, $itemType));
            }

            return self::RENDER_NOT_POSSIBLE;
        }

        $value = $this->getValue($item, $property);

        if (null !== $value) {
            if (!$propertyView->hasType()) {
                // Attempt to find the property type dynamically
                if (is_object($value)) {
                    $type = new Type(Type::BUILTIN_TYPE_OBJECT, false, get_class($value));
                } else {
                    $type = new Type(TypeUtil::getInternalType($value));
                }
            } else {
                $type = $propertyView->getType();
            }

            return $this->renderValue($type, $value, $options);
        }

        return $this->renderValue(new Type(Type::BUILTIN_TYPE_NULL), $value, $options);
    }

    /**
     * Render a single item property
     *
     * @param object $item
     *   Item on which to find the property
     * @param string|PropertyView $propery
     *   Property name
     * @param mixed[] $options
     *   Display options for the property
     *
     * @return string
     */
    public function renderItemProperty($item, $property, array $options = [])
    {
        if ($property instanceof PropertyView) {
            return $this->renderProperty($item, $property);
        }

        if (!is_object($item)) {
            if ($this->debug) {
                throw new PropertyTypeError(sprintf("Item is not an object %s found instead while rendering the '%s' property", gettype($item), $property));
            }
            return self::RENDER_NOT_POSSIBLE;
        }

        $type = null;
        $class = get_class($item);
        $types = $this->propertyInfo->getTypes($class, $property);

        if ($types) {
            $type = reset($types);
        }

        return $this->renderProperty($item, new PropertyView($property, $type, $options));
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
    public function getfilterDefinition($filters)
    {
        $definition = [];

        /** @var \MakinaCorpus\Dashboard\Datasource\Filter $filter */
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

        /** @var \MakinaCorpus\Dashboard\Datasource\Filter $filter */
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
