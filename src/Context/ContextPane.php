<?php

namespace MakinaCorpus\Calista\Context;

use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Calista\Event\ContextPaneEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ContextPane
{
    private $items = [];
    private $dispatcher;
    private $tabs = [];
    private $actions = [];
    private $defaultTab = null;

    /**
     * Default constructor
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Lazy initialise the object
     */
    public function init()
    {
        $event = new ContextPaneEvent($this);

        $this->dispatcher->dispatch(ContextPaneEvent::EVENT_INIT, $event);
    }

    /**
     * Add an tab to the contextual pane
     *
     * @param mixed $key
     *   Tab identifier
     * @param string $label
     *   Human-readable lavbel
     * @param string $icon
     *   Icon name for this tab
     * @param int $priority
     *   Will determine order
     * @param int $messageCount
     *   An arbitrary number that will be displayed as a label over the icon
     *   but hidden when 0
     *
     * @return $this
     */
    public function addTab($key, $label, $icon, $priority = 0, $messageCount = 0)
    {
        $this->tabs[$key] = [
            'priority'  => $priority,
            'key'       => $key,
            'label'     => $label,
            'icon'      => $icon,
            'count'     => $messageCount,
        ];
        $this->items[$key] = [];

        return $this;
    }

    /**
     * Add an item to the contextual pane
     *
     * @param mixed $value
     *   Anything that can be rendered
     * @param string $tab
     *   Tab identifier
     * @param int $priority
     *   Will determine order
     *
     * @return $this
     */
    public function add($value, $tab, $priority = 0)
    {
        if (!empty($value)) {
            $this->items[$tab][$priority][] = $value;
        }

        return $this;
    }

    /**
     * Is the context empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Get all ordered pane items, indexed by tab key.
     *
     * @return mixed[]
     *   Array of renderable items
     */
    public function getAll()
    {

        foreach ($this->getTabs() as $key => $label) {
            ksort($this->items[$key]);
        }

        return $this->items;
    }

    /**
     * Get all ordered tabs, indexed by tab key.
     *
     * @return string[][]
     *   Orderer tab definitions arrays
     */
    public function getTabs()
    {
        uasort($this->tabs, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $this->tabs;
    }

    /**
     * Does this tab is set
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasTab($name)
    {
        return isset($this->tabs[$name]);
    }

    /**
     * Get the default tab key, or first tab key if none set.
     *
     * @return null|string
     */
    public function getDefaultTab()
    {
        return $this->defaultTab ? $this->defaultTab : 'default';
    }

    /**
     * Get the real default tab key.
     *
     * @return null|string
     */
    public function getRealDefaultTab()
    {
        return $this->defaultTab;
    }

    /**
     * Set the default tab key.
     *
     * @param null $defaultTab
     *
     * @return $this
     */
    public function setDefaultTab($defaultTab)
    {
        $this->defaultTab = $defaultTab;

        return $this;
    }

    /**
     * Add a group of actions for this context.
     *
     * @param Action[] $actions
     * @param string $title
     * @param string $icon
     * @param boolean $showTitle
     *
     * @return $this
     */
    public function addActions($actions, $title = null, $icon = null, $showTitle = false)
    {
        if ($actions) {
            $this->actions[] = [
                'title'     => $title,
                'icon'      => $icon,
                'showTitle' => empty($icon) ? true : $showTitle,
                'actions'   => $actions,
                'raw'       => (!is_array($actions) || !reset($actions) instanceof Action)
            ];
        }

        return $this;
    }

    /**
     * Get all actions for this context
     *
     * @return string[][]
     *   Primary action descriptions arrays
     */
    public function getActions()
    {
        return $this->actions;
    }
}
