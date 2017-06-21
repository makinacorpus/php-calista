<?php

namespace MakinaCorpus\Dashboard\Drupal\EventDispatcher;

use MakinaCorpus\Dashboard\Event\TwigViewEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugs additionnal JavaScript and CSS when using an HTML view
 */
class TwigViewEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            TwigViewEvent::EVENT_VIEW => [
                ['onTwigView', 0],
            ],
        ];
    }

    /**
     * Add JS libraries
     *
     * @param \MakinaCorpus\Dashboard\Event\TwigViewEvent $event
     */
    public function onTwigView(TwigViewEvent $event)
    {
        if (function_exists('drupal_add_library')) {
            drupal_add_library('udashboard', 'udashboard_page');

            $seven = variable_get('udashboard.seven_force');
            if (null === $seven && 'seven' === $GLOBALS['theme']) {
                drupal_add_library('udashboard', 'udashboard_seven');
            } elseif (true === $seven) {
                drupal_add_library('udashboard', 'udashboard_seven');
            }

            /*
            if ($event->getView()->visualSearchIsEnabled()) {
                drupal_add_library('udashboard', 'udashboard_search');
            }
             */
        }
    }
}
