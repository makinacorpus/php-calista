<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\DependencyInjection\DynamicPageDefinition;

/**
 * Tests a very broken dynamic page definition
 */
class BrokenDynamicPageDefinitionOne extends DynamicPageDefinition
{
    // Keep templates here in order to avoid a few exception
    protected $templates = ['default' => 'module:udashboard:views/Page/page.html.twig'];

    public $one;
    public $someField;

    /**
     * Too many required parameters, this will fail during definition creation
     */
    public function renderSomeField($a, $b, $c, $d)
    {
        return '';
    }
}
