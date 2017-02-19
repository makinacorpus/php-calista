<?php

namespace MakinaCorpus\Drupal\Dashboard\Twig\Extension;

use MakinaCorpus\Drupal\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Page\Filter;

/**
 * Displays any object's actions
 */
class ActionExtension extends \Twig_Extension
{
    private $actionRegistry;

    /**
     * Default constructor
     *
     * @param ActionRegistry $actionRegistry
     */
    public function __construct(ActionRegistry $actionRegistry)
    {
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('udashboard_primary', [$this, 'renderPrimaryActions'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('udashboard_actions', [$this, 'renderActions'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('udashboard_actions_raw', [$this, 'renderActionsRaw'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('udashboard_query_param', [$this, 'flattenQueryParam']),
        ];
    }

    /**
     * Flatten query param if array
     *
     * @param string|string[] $value
     */
    public function flattenQueryParam($value)
    {
        if (is_array($value)) {
            return implode(Filter::URL_VALUE_SEP, $value);
        }

        return $value;
    }

    /**
     * Render actions
     *
     * @param mixed $item
     *   Item for which to display actions
     * @param string $icon
     *   Icon identifier for primary button
     * @param string $mode
     *   Can be 'link' or 'icon', determine only the primary icon style
     * @param string $title
     *   Title to display in place of primary actions
     * @param string $showTitle
     *   Should title should be displaye
     *
     * @return mixed
     *   Rendered actions
     */
    public function renderActions($item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        return $this->renderActionsRaw($this->actionRegistry->getActions($item), $icon, $mode, $title, $showTitle);
    }

    /**
     * Render primary actions only
     *
     * @param mixed $item
     *   Item for which to display actions
     * @param string $icon
     *   Icon identifier for primary button
     * @param string $mode
     *   Can be 'link' or 'icon', determine only the primary icon style
     * @param string $title
     *   Title to display in place of primary actions
     * @param string $showTitle
     *   Should title should be displaye
     *
     * @return mixed
     *   Rendered actions
     */
    public function renderPrimaryActions($item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        return $this->renderActionsRaw($this->actionRegistry->getActions($item, true), $icon, $mode, $title, $showTitle);
    }

    /**
     * Render arbitrary actions
     *
     * @param Action[] $action
     *   Actions to render
     * @param string $icon
     *   Icon identifier for primary button
     * @param string $mode
     *   Can be 'link' or 'icon', determine only the primary icon style
     * @param string $title
     *   Title to display in place of primary actions
     * @param string $showTitle
     *   Should title should be displaye
     *
     * @return mixed
     *   Rendered actions
     */
    public function renderActionsRaw($actions, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        // @todo still based upon Drupal, needs fixing
        $output = [
            '#theme'      => 'udashboard_actions',
            '#actions'    => $actions,
            '#icon'       => $icon,
            '#mode'       => $mode,
            '#title'      => $title,
            '#show_title' => $showTitle,
        ];

        return drupal_render($output);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'udashboard_action';
    }
}
