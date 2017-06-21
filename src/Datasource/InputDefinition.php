<?php

namespace MakinaCorpus\Dashboard\Datasource;

use MakinaCorpus\Dashboard\Error\ConfigurationError;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Input query definition and sanitizer
 *
 * @codeCoverageIgnore
 */
class InputDefinition
{
    private $allowedFilters = [];
    private $allowedSorts = [];
    private $options = [];

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

        $this->allowedFilters = $datasource->getAllowedFilters();
        $this->allowedSorts = $datasource->getAllowedSorts();

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

        if ($this->options['base_query']) {
            foreach (array_keys($this->options['base_query']) as $name) {
                if (!in_array($name, $this->allowedFilters)) {
                    throw new ConfigurationError(sprintf("'%s' base query filter is not a datasource allowed filter", $name));
                }
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
            'base_query'        => [],
            'display_param'     => 'display',
            'limit_allowed'     => false,
            'limit_default'     => Query::LIMIT_DEFAULT,
            'limit_param'       => 'limit',
            'pager_enable'      => true,
            'pager_param'       => 'page',
            'search_enable'     => false,
            'search_parse'      => false,
            'search_param'      => 's',
            'sort_field_param'  => 'st',
            'sort_order_param'  => 'by',
        ]);

        $resolver->setAllowedTypes('base_query', ['array']);
        $resolver->setAllowedTypes('display_param', ['string']);
        $resolver->setAllowedTypes('limit_allowed', ['numeric', 'bool']);
        $resolver->setAllowedTypes('limit_default', ['numeric']);
        $resolver->setAllowedTypes('limit_param', ['string']);
        $resolver->setAllowedTypes('pager_enable', ['numeric', 'bool']);
        $resolver->setAllowedTypes('pager_param', ['string']);
        $resolver->setAllowedTypes('search_enable', ['numeric', 'bool']);
        $resolver->setAllowedTypes('search_parse', ['numeric', 'bool']);
        $resolver->setAllowedTypes('search_param', ['string']);
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
     */
    public function getAllowedFilters()
    {
        return $this->allowedFilters;
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
        return in_array($name, $this->allowedFilters);
    }

    /**
     * Get allowed sort field list
     *
     * @return string[]
     */
    public function getAllowedSorts()
    {
        return $this->allowedSorts;
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
        return in_array($name, $this->allowedSorts);
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
}
