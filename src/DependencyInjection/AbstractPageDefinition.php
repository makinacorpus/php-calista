<?php

namespace MakinaCorpus\Dashboard\DependencyInjection;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\View\ViewDefinition;

/**
 * Base implementation, returns the defaults
 */
abstract class AbstractPageDefinition implements PageDefinitionInterface
{
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
    public function getViewDefinition()
    {
        return new ViewDefinition();
    }
}
