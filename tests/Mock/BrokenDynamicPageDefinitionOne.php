<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\DependencyInjection\DynamicPageDefinition;

/**
 * Tests a very broken dynamic page definition
 */
class BrokenDynamicPageDefinitionOne extends DynamicPageDefinition
{
    // Keep templates here in order to avoid a few exception
    protected $templates = ['default' => '@calista/page/page.html.twig'];

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
