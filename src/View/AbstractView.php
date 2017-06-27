<?php

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\DependencyInjection\ServiceTrait;
use MakinaCorpus\Calista\Util\TypeUtil;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Boilerplate code for view implementations.
 */
abstract class AbstractView implements ViewInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use ServiceTrait;

    /**
     * Aggregate properties from the ViewDefinition
     *
     * @param ViewDefinition $viewDefinition
     * @param string $class
     *
     * @return PropertyView[]
     */
    protected function normalizeProperties(ViewDefinition $viewDefinition, $class)
    {
        $ret = [];

        $properties = $viewDefinition->getDisplayedProperties();
        $propertyInfoExtractor = null;

        if ($this->container && $this->container->has('property_info')) {
            /** @var \Symfony\Component\PropertyInfo\PropertyInfoExtractor $propertyInfoExtractor */
            $propertyInfoExtractor = $this->container->get('property_info');
        }
        if (!$properties) {
            if ($propertyInfoExtractor) {
                $properties = $propertyInfoExtractor->getProperties($class);
            }
        }
        // The property info extractor might return null if nothing was found
        if (!$properties) {
            $properties = [];
        }

        foreach ($properties as $name) {
            if (!$viewDefinition->isPropertyDisplayed($name)) {
                continue;
            }

            $type = null;

            $options = $viewDefinition->getPropertyDisplayOptions($name);

            if (empty($options['label'])) {
                if ($propertyInfoExtractor) {
                    $options['label'] = $propertyInfoExtractor->getShortDescription($class, $name);
                }
                // Property info component might still return null here, give
                // the user a sensible fallback
                if (empty($options['label'])) {
                    $options['label'] = $name;
                }
            }

            // Determine data type from whatever we can find, still type can be
            // enforced by the user, at his own risks
            if (!empty($options['type'])) {
                $type = TypeUtil::getTypeInstance($options['type']);

            } else if ($propertyInfoExtractor) {
                $types = $propertyInfoExtractor->getTypes($class, $name);

                if ($types) {
                    if (is_array($types)) {
                        $type = reset($types);
                    } else {
                        $type = $types;
                    }
                }
            }

            $ret[$name] = new PropertyView($name, $type, $options);
        }

        return $ret;
    }
}
