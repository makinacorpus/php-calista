<?php

namespace MakinaCorpus\Dashboard\Event;

use MakinaCorpus\Dashboard\Page\PageBuilder;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event related toPageBuilder
 */
final class PageBuilderEvent extends Event
{
    /**
     * @var \MakinaCorpus\Dashboard\Page\PageBuilder
     */
    private $builder;

    /**
     * PageBuilderEvent constructor.
     *
     * @param \MakinaCorpus\Dashboard\Page\PageBuilder $builder
     */
    public function __construct(PageBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @return \MakinaCorpus\Dashboard\Page\PageBuilder
     */
    public function getPageBuilder()
    {
        return $this->builder;
    }
}
