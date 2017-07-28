<?php

namespace MakinaCorpus\Calista\DependencyInjection;

use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\View\ViewInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do it properly
 */
final class ViewFactory
{
    private $container;
    private $datasourceClasses = [];
    private $datasourceServices = [];
    private $pageClasses = [];
    private $pageServices = [];
    private $viewClasses = [];
    private $viewServices = [];

    /**
     * Default constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register page types
     *
     * @param string[] $services
     *   Keys are names, values are service identifiers
     * @param string[] $classes
     *   Keys are class names, values are service identifiers
     */
    public function registerDatasources(array $services, array $classes = [])
    {
        $this->datasourceServices = $services;
        $this->datasourceClasses = $classes;
    }

    /**
     * Register page types
     *
     * @param string[] $services
     *   Keys are names, values are service identifiers
     */
    public function registerPageDefinitions(array $services, array $classes = [])
    {
        $this->pageServices = $services;
        $this->pageClasses = $classes;
    }

    /**
     * Register page types
     *
     * @param string[] $services
     *   Keys are names, values are service identifiers
     * @param string[] $classes
     *   Keys are class names, values are service identifiers
     */
    public function registerViews(array $services, array $classes = [])
    {
        $this->viewServices = $services;
        $this->viewClasses = $classes;
    }

    /**
     * Create instance
     *
     * @param string $class
     * @param string $name
     * @param string[] $services
     * @param string[] $classes
     *
     * @return object
     */
    private function createInstance($class, $name, array $services, array $classes)
    {
        if (isset($classes[$name])) {
            // Only attempt with the first, class lookup is not always a good move
            return $this->createInstance($class, reset($classes[$name]), $services, $classes);
        }

        if (isset($services[$name])) {
            $id = $services[$name];
        } else {
            $id = $name;
        }

        try {
            $instance = $this->container->get($id);

            if (!is_a($instance, $class)) {
                throw new ServiceNotFoundException(sprintf("service '%s' with id '%s' does not implement %s", $name, $id, $class));
            }
        } catch (ServiceNotFoundException $e) {

            if (class_exists($name)) {
                $instance = new $name();

                if (!is_a($instance, $class)) {
                    throw new ServiceNotFoundException(sprintf("class '%s' does not implement %s", $name, $class));
                }
            } else {
                throw new ServiceNotFoundException(sprintf("service '%s' service id '%s' does not exist in container or class does not exists", $name, $id));
            }
        }

        if ($instance instanceof ServiceInterface) {
            $instance->setId($id);
        }
        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
    }

    /**
     * List found components in
     */
    private function listComponents(array $services, array $classes)
    {
        $ret = [];

        foreach ($services as $id => $serviceId) {
            foreach ($classes as $class => $map) {
                if (isset($map[$id])) {
                    $ret[$id] = [
                        'service' => $serviceId,
                        'class'   => $class,
                    ];
                }
            }
        }

        return $ret;
    }

    /**
     * List datasources
     *
     * @return array
     *   Keys are datasource identifiers, values are an array of:
     *     - service: service identifier, might be the same as the identifier
     *     - class: datasource class
     */
    public function listDatasources()
    {
        return $this->listComponents($this->datasourceServices, $this->datasourceClasses);
    }

    /**
     * List pages
     *
     * @return array
     *   Keys are page identifiers, values are an array of:
     *     - service: service identifier, might be the same as the identifier
     *     - class: datasource class
     */
    public function listPages()
    {
        return $this->listComponents($this->pageServices, $this->pageClasses);
    }

    /**
     * List views
     *
     * @return array
     *   Keys are view identifiers, values are an array of:
     *     - service: service identifier, might be the same as the identifier
     *     - class: datasource class
     */
    public function listViews()
    {
        return $this->listComponents($this->viewServices, $this->viewClasses);
    }

    /**
     * Get pages implementing the given class
     *
     * I am not proud of this one, but as of now it helps dynamic driven
     * frameworks such as Drupal finding out page definitions and register
     * them to its own router.
     *
     * @param string $class
     *
     * @return PageDefinitionInterface[]
     *
     * @deprecated
     *   You should not use this method.
     */
    public function getPageDefinitionList($class)
    {
        $ret = [];

        $isInterface = false;
        if (!class_exists($class)) {
            throw new \BadMethodCallException(sprintf("class %s does not exists"));
        }

        foreach ($this->pageClasses as $pageClass => $names) {
            foreach ($names as $name) {
                $refClass = new \ReflectionClass($pageClass);

                if ($isInterface) {
                    if ($refClass->implementsInterface($class)) {
                        $ret[$name] = $this->getPageDefinition($name);
                    }
                } else {
                    if ($refClass->name === $class || $refClass->isSubclassOf($class)) {
                        $ret[$name] = $this->getPageDefinition($name);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Get datasource
     *
     * @param string $name
     *
     * @return DatasourceInterface
     */
    public function getDatasource($name)
    {
        return $this->createInstance(DatasourceInterface::class, $name, $this->datasourceServices, $this->datasourceClasses);
    }

    /**
     * Get page definition
     *
     * @param string $name
     *
     * @return PageDefinitionInterface
     */
    public function getPageDefinition($name)
    {
        return $this->createInstance(PageDefinitionInterface::class, $name, $this->pageServices, $this->pageClasses);
    }

    /**
     * Get view
     *
     * @param string $name
     *
     * @return ViewInterface
     */
    public function getView($name)
    {
        return $this->createInstance(ViewInterface::class, $name, $this->viewServices, $this->viewClasses);
    }
}
