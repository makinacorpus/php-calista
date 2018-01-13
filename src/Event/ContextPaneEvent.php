<?php

namespace MakinaCorpus\Calista\Event;

use MakinaCorpus\Calista\Context\ContextPane;
use Symfony\Component\EventDispatcher\Event;

class ContextPaneEvent extends Event
{
    const EVENT_INIT = 'calista.context_init';

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
