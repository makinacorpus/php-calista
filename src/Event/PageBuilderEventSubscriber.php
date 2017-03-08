<?php

namespace MakinaCorpus\Drupal\Dashboard\Event;

use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class PageBuilderEventSubscriber
 * @package MakinaCorpus\Drupal\Dashboard\Event
 */
class PageBuilderEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageBuilder::EVENT_VIEW => [
                ['onPageBuilderView', 0],
            ],
        ];
    }

    /**
     * Add JS libraries
     *
     * @param \MakinaCorpus\Drupal\Dashboard\Event\PageBuilderEvent $event
     */
    public function onPageBuilderView(PageBuilderEvent $event)
    {
        if (function_exists('drupal_add_library')) {
            drupal_add_library('udashboard', 'udashboard_page');

            $seven = variable_get('udashboard.seven_force');
            if (null === $seven && 'seven' === $GLOBALS['theme']) {
                drupal_add_library('udashboard', 'udashboard_seven');
            } elseif (true === $seven) {
                drupal_add_library('udashboard', 'udashboard_seven');
            }

            if ($event->getPageBuilder()->visualSearchIsEnabled()) {
                drupal_add_library('udashboard', 'udashboard_search');
            }
        }
    }
}
