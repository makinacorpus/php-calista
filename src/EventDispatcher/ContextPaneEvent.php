<?php

namespace MakinaCorpus\Drupal\Dashboard\EventDispatcher;

use MakinaCorpus\Drupal\Dashboard\Context\ContextPane;

use Symfony\Component\EventDispatcher\Event;

class ContextPaneEvent extends Event
{
    const EVENT_INIT = 'udashboard.context_init';

    private $contextPane;

    public function __construct(ContextPane $contextPane)
    {
        $this->contextPane = $contextPane;
    }

    /**
     * @return ContextPane
     */
    public function getContextPane()
    {
        return $this->contextPane;
    }
}
