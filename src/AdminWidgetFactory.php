<?php

namespace MakinaCorpus\Drupal\Dashboard;

use Drupal\Core\Form\FormBuilderInterface;
use MakinaCorpus\Drupal\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageTypeInterface;
use MakinaCorpus\Drupal\Dashboard\Table\AdminTable;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * God I hate to register more factories to the DIC, but we have some
 * dependencies that we should inject into pages, and only this allows
 * us to do it properly
 */
final class AdminWidgetFactory
{
    private $container;
    private $formBuilder;
    private $pageTypes = [];
    private $actionRegistry;
    private $eventDispatcher;
    private $debug;
    private $twig;

    /**
     * Default constructor
     *
     * @param ContainerInterface $container
     * @param FormBuilderInterface $formBuilder
     * @param ActionRegistry $actionRegistry
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ContainerInterface $container,
        FormBuilderInterface $formBuilder,
        ActionRegistry $actionRegistry,
        \Twig_Environment $twig,
        EventDispatcherInterface $eventDispatcher = null
    ) {
        $this->container = $container;
        $this->formBuilder = $formBuilder;
        $this->actionRegistry = $actionRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->debug = $twig->isDebug();
        $this->twig = $twig;
    }

    /**
     * Register page types
     *
     * @param string[] $types
     *   Keys are names, values are service identifiers
     */
    public function registerPageTypes($types)
    {
        $this->pageTypes = $types;
    }

    /**
     * Get page type
     *
     * @param string $name
     *
     * @return PageTypeInterface
     */
    public function getPageType($name)
    {
        if (isset($this->pageTypes[$name])) {
            $id = $this->pageTypes[$name];
        } else {
            $id = $name;
        }

        try {
            $instance = $this->container->get($id);

            if (!$instance instanceof PageTypeInterface) {
                throw new \InvalidArgumentException(sprintf("page builder '%s' with service id '%s' does not implement %s", $name, $id, PageTypeInterface::class));
            }
        } catch (ServiceNotFoundException $e) {

            if (class_exists($name)) {
                $instance = new $name();

                if (!$instance instanceof PageTypeInterface) {
                    throw new \InvalidArgumentException(sprintf("class '%s' does not implement %s", $name, PageTypeInterface::class));
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
     * Create a page builder without page type
     *
     * @return PageBuilder
     */
    public function createPageBuilder()
    {
        return new PageBuilder($this->twig, $this->eventDispatcher);
    }

    /**
     * Get the page builder
     *
     * @param string $name
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \MakinaCorpus\Drupal\Dashboard\Page\PageBuilder
     */
    public function getPageBuilder($name, Request $request)
    {
        $type = $this->getPageType($name);
        $builder = new PageBuilder($this->twig, $this->eventDispatcher);

        $type->build($builder, $request);
        $builder->setId($name);

        return $builder;
    }

    /**
     * Get a new admin table
     *
     * @param string $name
     * @param array $attributes
     * @return \MakinaCorpus\Drupal\Dashboard\Table\AdminTable
     */
    public function getTable($name, $attributes = [])
    {
        return new AdminTable($name, $attributes, $this->eventDispatcher);
    }
}
