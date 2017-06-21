<?php

namespace MakinaCorpus\Dashboard\Event;

use MakinaCorpus\Dashboard\View\Html\TwigView;
use Symfony\Component\EventDispatcher\Event;

/**
 * Raised when a view is rendered
 */
final class ViewEvent extends Event
{
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
