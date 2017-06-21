<?php

namespace MakinaCorpus\Dashboard\Drupal\EventDispatcher;

use MakinaCorpus\Dashboard\Event\ViewEvent;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Plugs additionnal JavaScript and CSS when using an HTML view
 */
class ViewEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ViewEvent::EVENT_VIEW => [
                ['onTwigView', 0],
            ],
        ];
    }

    /**
     * Add JS libraries
     *
     * @param ViewEvent $event
     */
    public function onTwigView(ViewEvent $event)
    {
        $view = $event->getView();

        if (function_exists('drupal_add_library') && $view instanceof TwigView) {
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
