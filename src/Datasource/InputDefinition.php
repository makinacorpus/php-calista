<?php

namespace MakinaCorpus\Dashboard\Datasource;

use MakinaCorpus\Dashboard\Error\ConfigurationError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\Request;

/**
 * Input query definition and sanitizer
 *
 * @codeCoverageIgnore
 */
class InputDefinition
{
    private $filterLabels = [];
    private $filters = [];
    private $options = [];
    private $sortLabels = [];

    /**
     * Build an instance from an array
     *
     * @param mixed[] $options
     */
    public function __construct(DatasourceInterface $datasource, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        // Normalize filters and sorts
        $this->filters = $datasource->getFilters();
        foreach ($this->filters as $filter) {
            $this->filterLabels[$filter->getField()] = $filter->getTitle();
        }
        $this->sortLabels = $datasource->getSorts();

        // Do a few consistency checks based upon the datasource capabilities
        if (!$datasource->supportsFulltextSearch()) {
            if ($this->options['search_enable'] && !$this->options['search_parse']) {
                throw new ConfigurationError("datasource cannot do fulltext search, yet it is enabled, but search parse is disabled");
            }
        }
        if (!$datasource->supportsPagination()) {
            if ($this->options['pager_enable']) {
                throw new ConfigurationError("datasource cannot do paging, yet it is enabled");
            }
        }

        // Ensure given base query only contains legitimate field names
        if ($this->options['base_query']) {
            foreach (array_keys($this->options['base_query']) as $name) {
                if (!$this->isFilterAllowed($name)) {
                    throw new ConfigurationError(sprintf("'%s' base query filter is not a datasource allowed filter", $name));
                }
            }
        }

        // Set the default sort if none was given by the user, yell if user
        // gave one which is not supported by the datasource
        if (empty($this->options['sort_default_field'])) {
            $this->options['sort_default_field'] = key($this->sortLabels);
        } else {
            if (!$this->isSortAllowed($this->options['sort_default_field'])) {
                throw new ConfigurationError(sprintf("'%s' sort field is not a datasource allowed sort field", $this->options['sort_default_field']));
            }
        }
    }

    /**
     * Build options resolver
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'base_query'          => [],
            'display_param'       => 'display',
            'limit_allowed'       => false,
            'limit_default'       => Query::LIMIT_DEFAULT,
            'limit_param'         => 'limit',
            'pager_enable'        => true,
            'pager_param'         => 'page',
            'search_enable'       => false,
            'search_param'        => 's',
            'search_parse'        => false,
            'sort_default_field'  => '',
            'sort_default_order'  => Query::SORT_DESC,
            'sort_field_param'    => 'st',
            'sort_order_param'    => 'by',
        ]);

        $resolver->setAllowedTypes('base_query', ['array']);
        $resolver->setAllowedTypes('display_param', ['string']);
        $resolver->setAllowedTypes('limit_allowed', ['numeric', 'bool']);
        $resolver->setAllowedTypes('limit_default', ['numeric']);
        $resolver->setAllowedTypes('limit_param', ['string']);
        $resolver->setAllowedTypes('pager_enable', ['numeric', 'bool']);
        $resolver->setAllowedTypes('pager_param', ['string']);
        $resolver->setAllowedTypes('search_enable', ['numeric', 'bool']);
        $resolver->setAllowedTypes('search_param', ['string']);
        $resolver->setAllowedTypes('search_parse', ['numeric', 'bool']);
        $resolver->setAllowedTypes('sort_default_field', ['string']);
        $resolver->setAllowedTypes('sort_default_order', ['string']);
        $resolver->setAllowedTypes('sort_field_param', ['string']);
        $resolver->setAllowedTypes('sort_order_param', ['string']);
    }

    /**
     * Get base query
     *
     * @return string[]
     */
    public function getBaseQuery()
    {
        return $this->options['base_query'];
    }

    /**
     * Get allowed filterable field list
     *
     * @return string[]
     *   Keys are field name, values are human readable labels
     */
    public function getAllowedFilters()
    {
        return $this->filterLabels;
    }

    /**
     * Is the given filter field allowed
     *
     * @param string $name
     *
     * @return bool
     */
    public function isFilterAllowed($name)
    {
        return isset($this->filterLabels[$name]);
    }

    /**
     * Get allowed sort field list
     *
     * @return string[]
     *   Keys are field name, values are human readable labels
     */
    public function getAllowedSorts()
    {
        return $this->sortLabels;
    }

    /**
     * Is the given sort field allowed
     *
     * @param string $name
     *
     * @return bool
     */
    public function isSortAllowed($name)
    {
        return isset($this->sortLabels[$name]);
    }

    /**
     * Get display parameter name
     *
     * @return string
     */
    public function getDisplayParameter()
    {
        return $this->options['display_param'];
    }

    /**
     * Can the query change the limit
     *
     * @return bool
     */
    public function isLimitAllowed()
    {
        return $this->options['limit_allowed'];
    }

    /**
     * Get the default limit
     *
     * @return int
     */
    public function getDefaultLimit()
    {
        return $this->options['limit_default'];
    }

    /**
     * Get the limit parameter name
     *
     * @return string
     */
    public function getLimitParameter()
    {
        return $this->options['limit_param'];
    }

    /**
     * Is paging enabled
     *
     * @return bool
     */
    public function isPagerEnabled()
    {
        return $this->options['pager_enable'];
    }

    /**
     * Get page parameter
     *
     * @return string
     */
    public function getPagerParameter()
    {
        return $this->options['pager_param'];
    }

    /**
     * Is full search enabled
     *
     * @return bool
     */
    public function isSearchEnabled()
    {
        return $this->options['search_enable'];
    }

    /**
     * Is search parsed
     *
     * @return bool
     */
    public function isSearchParsed()
    {
        return $this->options['search_parse'];
    }

    /**
     * Get search parameter name
     *
     * @return string
     */
    public function getSearchParameter()
    {
        return $this->options['search_param'];
    }

    /**
     * Get sort field parameter
     *
     * @return string
     */
    public function getSortFieldParameter()
    {
        return $this->options['sort_field_param'];
    }

    /**
     * Get sort order parameter
     *
     * @return string
     */
    public function getSortOrderParameter()
    {
        return $this->options['sort_order_param'];
    }

    /**
     * Get default sort field
     *
     * @return string
     */
    public function getDefaultSortField()
    {
        return $this->options['sort_default_field'];
    }

    /**
     * Get default sort order
     *
     * @return string
     */
    public function getDefaultSortOrder()
    {
        return $this->options['sort_default_order'];
    }

    /**
     * Create query from array
     *
     * @param array $input
     *
     * @return Query
     */
    public function createQueryFromArray(array $input)
    {
        return (new QueryFactory())->fromArray($this, $input);
    }

    /**
     * Create query from request
     *
     * @param Request $request
     *
     * @return Query
     */
    public function createQueryFromRequest(Request $request)
    {
        return (new QueryFactory())->fromRequest($this, $request);
    }
}
