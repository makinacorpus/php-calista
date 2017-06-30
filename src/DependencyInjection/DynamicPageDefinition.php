<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use Doctrine\Common\Annotations\Reader;
use MakinaCorpus\Calista\Annotation\Property;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Datasource\InputDefinition;
use MakinaCorpus\Calista\Error\ConfigurationError;
use MakinaCorpus\Calista\Util\TypeUtil;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Dynamic, introspection based, view definition.
 *
 * Extend this class if you wish to have a language-natural way of defining
 * your views instead of using the options array.
 *
 * Each public property will be considered as a property to display within
 * the view, added automatically to the 'properties' option of the
 * ViewDefinition object.
 *
 * You can set default values on public properties, they will be dropped, but
 * the default value type will be used to force the exposed type to the final
 * template for display.
 *
 * Each property can have an associated render"Property" method, for example:
 *
 *   - 'id' property would have a 'renderId()' method
 *
 *   - 'someStuff' property would have a 'renderSomeStuff()' method
 *
 * which will be used a render callback within the final template. Those
 * methods MUST NOT have more than three required parameters, which are
 * (mixed $value, array $options, mixed $item) - which is the signature for
 * the render callbacks as defined and documented by the Twig extension.
 *
 * Please note that render methods do upper case the first letter of the
 * property name.
 *
 * It handles the datasource for you, you may declare it different ways:
 *
 *  - by calling setDatasource(DatasourceInterface) from your constructor and
 *    injecting the service via your constructor
 *
 *  - by setting a default value on the $datasourceId property which is the
 *    datasource service identifier
 *
 *  - by calling setDatasourceId(string) from your constructor and injecting
 *    the service identifier
 *
 *  - defining your page definition as a service and adding a call to the
 *    setDatasource(DatasourceInterface) method
 *
 * If the given datasource identifier is a class name, and if the service does
 * not exists in the container, it will be instanciated directly without any
 * constructor argument.
 *
 * This behavior is the same with the 'view_type' and 'templates' display
 * options, which are the two that you will the most oftenly use:
 *
 *   - view_type parameter is handled by the $viewType class property
 *
 *   - templates parameter is handled by the $templates class property
 */
abstract class DynamicPageDefinition extends AbstractPageDefinition implements ContainerAwareInterface
{
    private $annotationReader;
    private $container;
    private $datasource;
    private $debug = false;
    protected $datasourceId = '';
    protected $templates;
    protected $viewType;

    /**
     * Set annotation reader, if available
     *
     * @param Reader $annotationReader
     */
    final public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * {@inheritdoc}
     */
    final public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;

        if ($container->hasParameter('kernel.debug')) {
            $this->debug = (bool)$container->getParameter('kernel.debug');
        }
    }

    /**
     * Inject the datasource
     *
     * @param DatasourceInterface $datasource
     */
    final public function setDatasource(DatasourceInterface $datasource)
    {
        if ($this->datasource && $this->debug) {
            throw new ConfigurationError("you are injecting the datasource twice");
        }

        $this->datasource = $datasource;
    }

    /**
     * Inject the datasource service identifier
     *
     * @param string $serviceId
     */
    final public function setDatasourceId($serviceId)
    {
       if (isset($this->datasource)) {
            if ($this->debug) {
                throw new ConfigurationError("changing the datasource identifier while datasource is already instanciated has no effect");
            }
        } else {
            if ($this->datasourceId && $this->datasourceId !== $serviceId) {
                if ($this->debug) {
                    throw new ConfigurationError("you are changing the datasource identifier twice");
                }
            }
            $this->datasourceId = $serviceId;
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getDatasource()
    {
        if (!$this->datasource) {
            if (!$this->datasourceId) {
                throw new ConfigurationError("page defines no datasource nor datasource service identifier");
            }
            if (!$this->container) {
                throw new ConfigurationError("container is missing");
            }

            /** @var \MakinaCorpus\Calista\DependencyInjection\ViewFactory $registry */
            try {
                $registry = $this->container->get('calista.view_factory');
                $this->datasource = $registry->getDatasource($this->datasourceId);
            } catch (ServiceNotFoundException $e) {
                throw new ConfigurationError("could not find datasource", null, $e);
            }
        }

        return $this->datasource;
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
    final public function getViewDefinition()
    {
        $options = $this->getDisplayOptions();

        // Handles the view type argument
        if (isset($this->viewType)) {
            if (isset($options['view_type'])) {
                if ($this->debug) {
                    throw new ConfigurationError("you are overriding the 'view_type' parameter which already has a value via the \$viewType property");
                }
            } else {
                $options['view_type'] = $this->viewType;
            }
        }

        // Handles the template argument
        if (isset($this->templates)) {
            if (isset($options['templates'])) {
                if ($this->debug) {
                    throw new ConfigurationError("you are overriding the 'templates' parameter which already has a value via the \$templates property");
                }
            } else {
                $options['templates'] = $this->templates;
            }
        }

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

            $name = $propertyRef->getName();
            $displayOptions = [];

            if ($this->annotationReader) {
                $annotation = $this->annotationReader->getPropertyAnnotation(new \ReflectionProperty($class, $name), Property::class);
                if ($annotation instanceof Property) {
                    $displayOptions = $annotation->getOptions();
                }
            }

            $type = TypeUtil::getInternalType($propertyRef->getValue($this));

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
                if (3 < $methodRef->getNumberOfRequiredParameters()) {
                    throw new ConfigurationError(sprintf("method '%s' for rendering property '%s' cannot have more than 3 required parameters (mixed \$value, array \$options, mixed \$item)", $methodName, $name));
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
