<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\DependencyInjection\DynamicPageDefinition;
use MakinaCorpus\Calista\View\Html\TwigView;

/**
 * Tests dynamic page definition without options
 */
class DynamicPageDefinitionName extends DynamicPageDefinition
{
    protected $datasourceId = 'int_array_datasource';
    protected $templates = ['default' => '@calista/Page/page.html.twig'];
    protected $viewType = TwigView::class;

    public $id = 0;
    public $type = "";
    public $thousands = [];

    /**
     * Render thousands callback.
     */
    public function renderThousands($value)
    {
        return "I DID RENDER THOUSANDS";
    }
}
