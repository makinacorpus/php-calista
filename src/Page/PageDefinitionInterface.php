<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use Symfony\Component\HttpFoundation\Request;

/**
 * A page type is a re-usable specific page builder, that will allow you to,
 * once registered as a container service, benefit from AJAX capabilities of
 * the page builder.
 *
 * It also allows you to define once then re-use specific pages at various
 * places on your site.
 */
interface PageDefinitionInterface
{
    /**
     * Create configuration
     *
     * @param mixed[] $options = []
     *   Options overrides
     *
     * @return InputDefinition
     */
    public function createInputDefinition(array $options = []);

    /**
     * Build the page parameters
     *
     * @param TwigView $view
     * @param InputDefinition $inputDefinition
     * @param Request $request
     */
    public function build(TwigView $view, InputDefinition $inputDefinition, Request $request);
}
