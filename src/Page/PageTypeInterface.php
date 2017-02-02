<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

/**
 * Pages that needs to be able to be built from AJAX requests need to be
 * implemented using this interface and registered throught the container
 * with the tag 'udashboard.page_type'.
 */
interface PageTypeInterface
{
    /**
     * Build the page parameters
     *
     * @param PageBuilder $builder
     */
    public function build(PageBuilder $builder, Request $request);
}
