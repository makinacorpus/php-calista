<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use MakinaCorpus\Calista\Datasource\InputDefinition;
use MakinaCorpus\Calista\View\ViewDefinition;

/**
 * Base implementation, returns the defaults
 *
 * @codeCoverageIgnore
 */
abstract class AbstractPageDefinition implements PageDefinitionInterface
{
    use ServiceTrait;

    /**
     * Get default (non properties) display options
     *
     * Both 'view_type' and 'templates' can be handled automatically by this
     * parent class by settings a default value respectively on the
     * $viewType and $templates class properties.
     *
     * @return array
     */
    protected function getDisplayOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = [])
    {
        return new InputDefinition($this->getDatasource(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getViewDefinition(array $options = [])
    {
        return new ViewDefinition($options + $this->getDisplayOptions());
    }
}
