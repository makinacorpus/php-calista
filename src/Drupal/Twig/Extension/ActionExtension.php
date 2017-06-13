<?php

namespace MakinaCorpus\Dashboard\Drupal\Twig\Extension;

use MakinaCorpus\Dashboard\Drupal\Action\ActionRegistry;
use MakinaCorpus\Dashboard\Drupal\Action\Action;

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
            new \Twig_SimpleFunction('udashboard_button', [$this, 'renderSingleAction'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('udashboard_actions', [$this, 'renderActions'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('udashboard_actions_raw', [$this, 'renderActionsRaw'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render a singe action
     *
     * @param array $options
     *   Options that will be given to Action::create()
     *
     * @return string
     */
    public function renderSingleAction(array $options)
    {
        $output = [
            '#theme'  => 'udashboard_action_single',
            '#action' => Action::create($options),
        ];

        return drupal_render($output);
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
        return $this->renderActionsRaw($this->actionRegistry->getActions($item, false), $icon, $mode, $title, $showTitle);
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
