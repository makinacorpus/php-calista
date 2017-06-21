<?php

namespace MakinaCorpus\Dashboard\DependencyInjection;

use MakinaCorpus\Dashboard\View\ViewInterface;
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
    private $pageDefinitions = [];
    private $views = [];

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
     * @param string[] $definitions
     *   Keys are names, values are service identifiers
     */
    public function registerPageDefinitions($definitions)
    {
        $this->pageDefinitions = $definitions;
    }

    /**
     * Register page types
     *
     * @param string[] $views
     *   Keys are names, values are service identifiers
     */
    public function registerViews($views)
    {
        $this->views = $views;
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
        if (isset($this->pageDefinitions[$name])) {
            $id = $this->pageDefinitions[$name];
        } else {
            $id = $name;
        }

        try {
            $instance = $this->container->get($id);

            if (!$instance instanceof PageDefinitionInterface) {
                throw new \InvalidArgumentException(sprintf("page definition '%s' with service id '%s' does not implement %s", $name, $id, PageDefinitionInterface::class));
            }
        } catch (ServiceNotFoundException $e) {

            if (class_exists($name)) {
                $instance = new $name();

                if (!$instance instanceof PageDefinitionInterface) {
                    throw new \InvalidArgumentException(sprintf("class '%s' does not implement %s", $name, PageDefinitionInterface::class));
                }
            } else {
                throw new \InvalidArgumentException(sprintf("page definition '%s' with service id '%s' does not exist in container or class does not exists", $name, $id));
            }
        }

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
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
        if (isset($this->views[$name])) {
            $id = $this->views[$name];
        } else {
            $id = $name;
        }

        try {
            $instance = $this->container->get($id);

            if (!$instance instanceof ViewInterface) {
                throw new \InvalidArgumentException(sprintf("view '%s' with service id '%s' does not implement %s", $name, $id, ViewInterface::class));
            }
        } catch (ServiceNotFoundException $e) {

            if (class_exists($name)) {
                $instance = new $name();

                if (!$instance instanceof ViewInterface) {
                    throw new \InvalidArgumentException(sprintf("class '%s' does not implement %s", $name, ViewInterface::class));
                }
            } else {
                throw new \InvalidArgumentException(sprintf("view '%s' with service id '%s' does not exist in container or class does not exists", $name, $id));
            }
        }

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
    }
}
