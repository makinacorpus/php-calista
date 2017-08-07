<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Datasource\InputDefinition;
use MakinaCorpus\Calista\View\ViewDefinition;

/**
 * A page type is a re-usable specific page definition, that will allow you to,
 * once registered as a container service, benefit from AJAX capabilities of
 * the HTML pages.
 *
 * It also allows you to define once then re-use specific pages at various
 * places on your site.
 */
interface PageDefinitionInterface extends ServiceInterface
{
    /**
     * Create configuration
     *
     * @param mixed[] $options = []
     *   Options overrides from the controller or per site configuration
     *
     * @return InputDefinition
     */
    public function getInputDefinition(array $options = []);

    /**
     * Create view definition for this page
     *
     * @param mixed[] $options = []
     *   Options overrides from the controller or per site configuration
     *
     * @return ViewDefinition
     */
    public function getViewDefinition(array $options = []);

    /**
     * Get the associated datasource
     *
     * @return DatasourceInterface
     */
    public function getDatasource();
}
