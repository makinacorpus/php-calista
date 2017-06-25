<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\DependencyInjection\DynamicPageDefinition;

/**
 * Tests dynamic page definition without options
 */
class DynamicPageDefinitionService extends DynamicPageDefinition
{
    protected $datasourceId = '_test_datasource';
    protected $templates = ['default' => 'module:udashboard:views/Page/page.html.twig'];
    protected $viewType = 'udashboard.view.twig_page';

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
