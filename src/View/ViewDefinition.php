<?php

namespace MakinaCorpus\Dashboard\View;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * View definition sanitizer
 *
 * @codeCoverageIgnore
 */
class ViewDefinition
{
    private $allowedFilters = [];
    private $allowedSorts = [];
    private $options = [];

    /**
     * Build an instance from an array
     *
     * @param mixed[] $options
     */
    public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * InputDefinition option resolver
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'default_display'   => 'default',
            'enabled_filters'   => [],
            'show_filters'      => false,
            'show_pager'        => false,
            'show_search'       => false,
            'show_sort'         => false,
            'templates'         => [],
            'view_type'         => '',
        ]);

        $resolver->setRequired('view_type');

        $resolver->setAllowedTypes('default_display', ['string']);
        $resolver->setAllowedTypes('enabled_filters', ['array']);
        $resolver->setAllowedTypes('show_filters', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_pager', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_search', ['numeric', 'bool']);
        $resolver->setAllowedTypes('show_sort', ['numeric', 'bool']);
        $resolver->setAllowedTypes('templates', ['array']);
        $resolver->setAllowedTypes('view_type', ['string']);
    }

    /**
     * Get default display
     *
     * @return string
     */
    public function getDefaultDisplay()
    {
        return $this->options['default_display'];
    }

    /**
     * Get enabled filters
     *
     * @return string[]
     */
    public function getEnabledFilters()
    {
        return $this->options['enabled_filters'];
    }

    /**
     * Are filters enabled
     *
     * @param string $name
     *
     * @return bool
     */
    public function isFilterDisplayed($name)
    {
        return in_array($name, $this->options['enabled_filters']);
    }

    /**
     * Is search bar enabled
     *
     * @return bool
     */
    public function isSearchEnabled()
    {
        return $this->options['show_search'];
    }

    /**
     * Is sort enabled
     *
     * @return bool
     */
    public function isSortEnabled()
    {
        return $this->options['show_sort'];
    }

    /**
     * Is pager enabled
     *
     * @return bool
     */
    public function isPagerEnabled()
    {
        return $this->options['show_pager'];
    }

    /**
     * Get templates
     *
     * @return string[]
     *   Keys are display identifiers, values are template names
     */
    public function getTemplates()
    {
        return $this->options['templates'];
    }

    /**
     * Get view type
     *
     * @return string
     */
    public function getViewType()
    {
        return $this->options['view_type'];
    }
}
