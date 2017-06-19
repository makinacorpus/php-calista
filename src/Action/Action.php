<?php

namespace MakinaCorpus\Dashboard\Action;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
class Action
{
    /**
     * Create action from array
     *
     * @param mixed[] $options
     *
     * @return Action
     */
    static public function create($options)
    {
        $options += [
            'title'     => '',
            'route'     => '',
            'options'   => [],
            'icon'      => '',
            'priority'  => 0,
            'primary'   => true,
            'redirect'  => false,
            'disabled'  => false,
            'group'     => '',
        ];

        return new static(
            $options['title'],
            $options['route'],
            $options['options'],
            $options['icon'],
            $options['priority'],
            $options['primary'],
            $options['redirect'],
            $options['disabled'],
            $options['group']
        );
    }

    private $title = '';
    private $route = '';
    private $routeParameters = [];
    private $linkOptions = [];
    private $priority = 0;
    private $icon = '';
    private $primary = true;
    private $disabled = false;
    private $group = null;

    /**
     * Default constructor
     *
     * @param string $title
     *   Human readable action
     * @param string $route
     *   Symfony route, Drupal path or full URL
     * @param string|array $options
     *   Link options, see the l() and url() functions altogether if you're using Drupal
     *   or it will be used as route parameters for Symfony router
     *   It can be one of those values:
     *     'dialog' : load the page in a dialog
     *     'blank' : load with target=blank
     * @param string $icon
     *   Something that is a bootstrap glyphicon name (easiest way of theming
     *   this, sorry)
     * @param int $priority
     *   Global ordering priority for this action
     * @param boolean $primary
     *   If set to false, this action might be displayed into a second level
     *   actions dropdown instead of being directly accessible
     * @param boolean $addCurrentDestination
     *   If set to true, this code will automatically add the current page as
     *   a query destination for the action
     * @param boolean $disabled
     *   If set to true, action will be disabled
     * @param string $group
     *   An arbitrary string that will be used to group actions altogether
     */
    public function __construct($title, $route = '', $options = [], $icon = '', $priority = 0, $primary = true, $addCurrentDestination = false, $disabled = false, $group = '')
    {
        $this->title = $title;
        $this->route = $route;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->primary = $primary;
        $this->disabled = $disabled;
        $this->group = $group;

        if (is_array($options)) {
            $this->routeParameters = $options;
        } else {
            switch ($options) {

              case 'blank':
                  $this->linkOptions = [
                      'attributes' => ['target' => '_blank'],
                  ];
                  break;

              case 'ajax':
                  $addCurrentDestination = true;
                  $this->linkOptions = [
                      'attributes' => ['class' => ['use-ajax']],
                  ];
                  break;

              case 'dialog':
                  $this->routeParameters['minidialog'] = 1;
                  $this->linkOptions = [
                      'attributes' => ['class' => ['use-ajax', 'minidialog']],
                  ];
                  break;
            }
        }

        $this->linkOptions['attributes']['title'] = $this->title;

        if ($disabled) {
            $this->linkOptions['attributes']['class'][] = 'disabled';
        }

        if ($addCurrentDestination) {
            if (function_exists('drupal_get_destination')) {
                // Do not allow GET query parameter override
                if (empty($this->routeParameters['destination'])) {
                    $this->routeParameters += drupal_get_destination();
                }
            } else {
                // We are not in Drupal, and this is not implemented.
                // @todo sorry
            }
        }
    }

    /**
     * Get action group
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get action title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get action route, can be an already computed URL
     *
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Get route parameters
     *
     * @return string[]
     *   Route parameters (mostly GET query parameters)
     */
    public function getRouteParameters()
    {
        return $this->routeParameters;
    }

    /**
     * Get link options
     *
     * @return array
     *   For Drupal, this is a suitable array for l() and url() functions, whose
     *   only missing the 'query' key, query must be fetched calling the
     *   getRouteParameters() method.
     */
    public function getOptions()
    {
        return $this->linkOptions;
    }

    /**
     * Get icon
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get action priority (order in list)
     *
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Is the action primary
     *
     * @return bool
     */
    public function isPrimary()
    {
        return $this->primary;
    }

    /**
     * Is the action disabled
     *
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * Toggle primary mode
     *
     * @param bool $primary
     */
    public function setPrimary($primary)
    {
        $this->primary = $primary;
    }
}
