<?php

namespace MakinaCorpus\Dashboard\Datasource;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Configuration for the query
 *
 * @codeCoverageIgnore
 */
class Configuration
{
    /**
     * @var mixed[]
     */
    private $options = [];

    /**
     * Build an instance from an array
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $this->options = (new OptionsResolver())
            ->setDefaults([
                'display_param'     => 'display',
                'limit_allowed'     => false,
                'limit_default'     => Query::LIMIT_DEFAULT,
                'limit_param'       => 'limit',
                'pager_element'     => 0,
                'pager_enable'      => true,
                'pager_param'       => 'page',
                'search_enable'     => false,
                'search_parse'      => false,
                'search_param'      => 's',
                'sort_field_param'  => 'st',
                'sort_order_param'  => 'by',
            ])
            ->resolve($options)
        ;
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
     * Get pager element
     *
     * @return int
     *
     * @deprecated
     *   This is a Drupal only addition
     */
    public function getPagerElement()
    {
        return $this->options['pager_element'];
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
