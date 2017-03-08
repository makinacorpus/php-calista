<?php

namespace MakinaCorpus\Drupal\Dashboard\Event;

use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event related toPageBuilder
 */
final class PageBuilderEvent extends Event
{
    /**
     * @var \MakinaCorpus\Drupal\Dashboard\Page\PageBuilder
     */
    private $builder;

    /**
     * PageBuilderEvent constructor.
     *
     * @param \MakinaCorpus\Drupal\Dashboard\Page\PageBuilder $builder
     */
    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return \MakinaCorpus\Drupal\Dashboard\Page\PageBuilder
     */
    public function getPageBuilder()
    {
        return $this->builder;
    }
}
