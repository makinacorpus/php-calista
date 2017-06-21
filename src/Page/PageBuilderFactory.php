<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Action\ActionRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do it properly
 */
final class PageBuilderFactory
{
    private $container;
    private $formFactory;
    private $pageDefinitions = [];
    private $actionRegistry;
    private $eventDispatcher;
    private $debug;
    private $twig;

    /**
     * Default constructor
     *
     * @param ContainerInterface $container
     * @param FormFactory $formFactory
     * @param ActionRegistry $actionRegistry
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ContainerInterface $container,
        FormFactory $formFactory,
        ActionRegistry $actionRegistry,
        \Twig_Environment $twig,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        if (!$eventDispatcher) {
            $eventDispatcher = new EventDispatcher();
        }

        $this->container = $container;
        $this->formFactory = $formFactory;
        $this->actionRegistry = $actionRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $twig->isDebug();
        $this->twig = $twig;
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
     * Get page type
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
                throw new \InvalidArgumentException(sprintf("page builder '%s' with service id '%s' does not implement %s", $name, $id, PageDefinitionInterface::class));
            }
        } catch (ServiceNotFoundException $e) {

            if (class_exists($name)) {
                $instance = new $name();

                if (!$instance instanceof PageDefinitionInterface) {
                    throw new \InvalidArgumentException(sprintf("class '%s' does not implement %s", $name, PageDefinitionInterface::class));
                }
            } else {
                throw new \InvalidArgumentException(sprintf("page builder '%s' with service id '%s' does not exist in container or class does not exists", $name, $id));
            }
        }

        if ($instance instanceof ContainerAwareInterface) {
            $instance->setContainer($this->container);
        }

        return $instance;
    }

    /**
     * Initialize page builder
     */
    private function initializeBuilder(PageBuilder $builder, $name = null, Request $request = null)
    {
       if ($name) {
            if (!$request) {
                throw new \LogicException("you cannot fetch a page builder through a type without a request");
            }

            $builder->setId($name);

            $definition = $this->getPageDefinition($name);
            $inputDefinition = $definition->createInputDefinition();

            $definition->build($builder, $inputDefinition, $request);
        }
    }

    /**
     * Create a page builder with or without type
     *
     * @param string $name
     *   If given will use the given page type
     * @param Request $request
     *   Mandatory when name is given
     *
     * @return PageBuilder
     */
    public function createPageBuilder($name = null, Request $request = null)
    {
        $builder = new PageBuilder($this->twig, $this->eventDispatcher);
        $this->initializeBuilder($builder, $name, $request);

        return $builder;
    }

    /**
     * Create a SF form page builder without page type
     *
     * @param string $name
     *   If given will use the given page type
     * @param Request $request
     *   Mandatory when name is given
     *
     * @return FormPageBuilder
     */
    public function createFormPageBuilder($name = null, Request $request = null)
    {
        $builder = new FormPageBuilder($this->twig, $this->eventDispatcher, $this->formFactory);
        $this->initializeBuilder($builder, $name, $request);

        return $builder;
    }
}
