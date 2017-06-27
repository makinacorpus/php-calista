<?php

namespace MakinaCorpus\Calista\Twig;

use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Calista\Action\ActionRegistry;

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
            new \Twig_SimpleFunction('calista_primary', [$this, 'renderPrimaryActions'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('calista_button', [$this, 'renderSingleAction'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('calista_actions', [$this, 'renderActions'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('calista_actions_raw', [$this, 'renderActionsRaw'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    /**
     * Render a singe action
     *
     * @param \Twig_Environment $environment
     *   Twig environment
     * @param array $options
     *   Options that will be given to Action::create()
     * @param string $showTitle
     *   Should title should be displayed
     *
     * @return string
     */
    public function renderSingleAction(\Twig_Environment $environment, array $options, $showTitle = false)
    {
        return $environment->render('module:calista:views/Action/actions.html.twig', [
            'show_title'  => $showTitle,
            'action'      => Action::create($options),
        ]);
    }

    /**
     * Render actions
     *
     * @param \Twig_Environment $environment
     *   Twig environment
     * @param mixed $item
     *   Item for which to display actions
     * @param string $icon
     *   Icon identifier for primary button
     * @param string $mode
     *   Can be 'link' or 'icon', determine only the primary icon style
     * @param string $title
     *   Title to display in place of primary actions
     * @param string $showTitle
     *   Should title should be displayed
     *
     * @return mixed
     *   Rendered actions
     */
    public function renderActions(\Twig_Environment $environment, $item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        return $this->renderActionsRaw($environment, $this->actionRegistry->getActions($item, false), $icon, $mode, $title, $showTitle);
    }

    /**
     * Render primary actions only
     *
     * @param \Twig_Environment $environment
     *   Twig environment
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
    public function renderPrimaryActions(\Twig_Environment $environment, $item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        return $this->renderActionsRaw($environment, $this->actionRegistry->getActions($item, true), $icon, $mode, $title, $showTitle);
    }

    /**
     * Render arbitrary actions
     *
     * @param \Twig_Environment $environment
     *   Twig environment
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
    public function renderActionsRaw(\Twig_Environment $environment, array $actions, $icon = '', $mode = 'icon', $title = '', $showTitle = false)
    {
        $context = [
            'title'       => $title,
            'icon'        => $icon,
            'show_title'  => $showTitle,
            'mode'        => $mode,
        ];

        foreach ($actions as $key => $action) {
            /** @var $action \MakinaCorpus\Calista\Action\Action */
            // Remove actions for which the path is the same.
            /* @todo
            if (current_path() === $action->getRoute()) {
                continue;
            }
             */

            if ($action->isPrimary()) {
                $target = 'primary';
            } else {
                $target = 'secondary';
            }

            $context[$target][$action->getGroup()][$key] = $action;
        }

        foreach (['primary', 'secondary'] as $target) {
            if (isset($context[$target])) {
                foreach ($context[$target] as &$group) {
                    usort($group, function (Action $a, Action $b) {
                        return $a->getPriority() - $b->getPriority();
                    });
                }
            } else {
                $context[$target] = [];
            }
        }

        return $environment->render('module:calista:views/Action/actions.html.twig', $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'calista_action';
    }
}
