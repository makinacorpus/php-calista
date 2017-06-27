<?php

namespace MakinaCorpus\Calista\Event;

use MakinaCorpus\Calista\View\Html\TwigView;
use Symfony\Component\EventDispatcher\Event;

/**
 * Raised when a view is rendered
 *
 * @codeCoverageIgnore
 */
final class ViewEvent extends Event
{
    const EVENT_VIEW = 'view:view';
    const EVENT_SEARCH = 'view:search';

    /**
     * @var TwigView
     */
    private $view;

    /**
     * Default constructor
     *
     * @param TwigView $view
     */
    public function __construct(TwigView $view)
    {
        $this->view = $view;
    }

    /**
     * @return TwigView
     */
    public function getView()
    {
        return $this->view;
    }
}
