<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\DependencyInjection\DynamicPageDefinition;

/**
 * Tests dynamic page definition without options
 */
class DynamicPageDefinitionService extends DynamicPageDefinition
{
    protected $datasourceId = 'test_datasource';
    protected $templates = ['default' => '@calista/page/page.html.twig'];
    protected $viewType = 'calista.view.twig_page';

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
