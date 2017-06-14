<?php

namespace MakinaCorpus\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Dashboard\Datasource\Configuration;

/**
 * A page type is a re-usable specific page builder, that will allow you to,
 * once registered as a container service, benefit from AJAX capabilities of
 * the page builder.
 *
 * It also allows you to define once then re-use specific pages at various
 * places on your site.
 */
interface PageTypeInterface
{
    /**
     * Create configuration
     *
     * @param mixed[] $options = []
     *   Options overrides
     *
     * @return Configuration
     */
    public function createConfiguration(array $options = []);

    /**
     * Build the page parameters
     *
     * @param PageBuilder $builder
     * @param Configuration $configuration
     * @param Request $request
     */
    public function build(PageBuilder $builder, Configuration $configuration, Request $request);
}
