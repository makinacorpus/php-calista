<?php

namespace MakinaCorpus\Calista\Event;

use MakinaCorpus\Calista\Context\ContextPane;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * React upon framework events
 */
class KernelEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 0],
            ],
        ];
    }

    private $contextPane;

    /**
     * Default constructor
     */
    public function __construct(ContextPane $contextPane)
    {
        // @todo this is a global state, and I don't like it
        $this->contextPane = $contextPane;
    }

    /**
     * On request initialize context
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (HttpKernel::MASTER_REQUEST === $event->getRequestType()) {
            $this->contextPane->init();
        }
    }
}
