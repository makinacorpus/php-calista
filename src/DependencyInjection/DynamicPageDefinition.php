<?php

namespace MakinaCorpus\Dashboard\DependencyInjection;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\View\ViewDefinition;
use MakinaCorpus\Dashboard\Error\ConfigurationError;

/**
 * Dynamic, introspection based, view definition.
 *
 * Extend this class if you wish to have a language-natural way of defining
 * your views instead of using the options array.
 */
abstract class DynamicPageDefinition extends AbstractPageDefinition
{
    /**
     * {@inheritdoc}
     */
    public function getInputDefinition(array $options = [])
    {
        return new InputDefinition($this->getDatasource(), $options);
    }

    /**
     * Get default (non properties) display options
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
    final public function getViewDefinition()
    {
        $options = $this->getDisplayOptions();

        $class = get_class($this);
        $classRef = new \ReflectionClass($class);

        /** @var \ReflectionProperty $propertyRef */
        foreach ($classRef->getProperties() as $propertyRef) {

            // Only allow this class defined properties to act as definition
            if ($propertyRef->class !== $class) {
                continue;
            }
            if (!$propertyRef->isPublic()) {
                continue;
            }

            $displayOptions = [];

            $name = $propertyRef->getName();
            $type = gettype($propertyRef->getValue($this));

            // Allow to enfore the property type for display
            if ("NULL" !== $type) {
                $displayOptions['type'] = $type;
            }

            $methodName = 'render' . ucfirst($name);
            if ($classRef->hasMethod($methodName)) {
                $methodRef = $classRef->getMethod($methodName);

                if ($methodRef->isAbstract()) {
                    throw new ConfigurationError(sprintf("method '%s' for rendering property '%s' cannot be abstract", $methodName, $name));
                }
                if (!$methodRef->isPublic()) {
                    throw new ConfigurationError(sprintf("method '%s' for rendering property '%s' must be public", $methodName, $name));
                }
                if (2 < $methodRef->getNumberOfRequiredParameters()) {
                    throw new ConfigurationError(sprintf("method '%s' for rendering property '%s' cannot have more than 2 required parameters (mixed \$value, array \$options)", $methodName, $name));
                }

                $displayOptions['callback'] = [$this, $methodName];
            }

            if ($displayOptions) {
                $options['properties'][$name] = $displayOptions;
            } else {
                $options['properties'][$name] = true;
            }
        }

        return new ViewDefinition($options);
    }
}
