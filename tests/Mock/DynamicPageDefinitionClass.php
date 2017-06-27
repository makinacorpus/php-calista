<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\DependencyInjection\DynamicPageDefinition;
use MakinaCorpus\Calista\View\Html\TwigView;

/**
 * Tests dynamic page definition without options
 */
class DynamicPageDefinitionClass extends DynamicPageDefinition
{
    protected $datasourceId = IntArrayDatasource::class;
    protected $templates = ['default' => 'module:calista:views/Page/page.html.twig'];
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
