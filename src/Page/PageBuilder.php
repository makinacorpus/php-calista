<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\Datasource\QueryFactory;
use MakinaCorpus\Dashboard\Error\ConfigurationError;
use MakinaCorpus\Dashboard\Event\PageBuilderEvent;
use MakinaCorpus\Dashboard\View\ViewDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @todo
 *   - Remove buiseness methods from this oibject and move them to "Page"
 *   - widget factory should return a page, not a builder
 */
class PageBuilder
{
    const EVENT_VIEW = 'pagebuilder:view';
    const EVENT_SEARCH = 'pagebuilder:search';

    private $datasource;
    private $debug = false;
    private $dispatcher;
    private $id;
    private $inputDefinition;
    private $viewDefinition;
    private $twig;

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
     */
    public function __construct(\Twig_Environment $twig, EventDispatcherInterface $dispatcher)
    {
        $this->twig = $twig;
        $this->debug = $twig->isDebug();
        $this->dispatcher = $dispatcher;
    }

    /**
     * Set builder identifier
     *
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        if ($this->id && $this->id !== $id) {
            throw new \LogicException("Cannot change a page builder identifier.");
        }

        $this->id = $id;

        return $this;
    }

    /**
     * Set input definition
     *
     * @param InputDefinition $inputDefinition
     *
     * @return $this
     */
    public function setInputDefinition(InputDefinition $inputDefinition)
    {
        if ($this->inputDefinition) {
            throw new ConfigurationError("you are overriding an already set input configuration");
        }

        $this->inputDefinition = $inputDefinition;

        return $this;
    }

    /**
     * Get input definition
     *
     * @return InputDefinition
     */
    public function getInputDefinition()
    {
        if (!$this->inputDefinition) {
            $this->inputDefinition = new InputDefinition($this->getDatasource());
        }

        return $this->inputDefinition;
    }

    /**
     * Set configuration
     *
     * @param ViewDefinition $viewDefinition
     *
     * @return $this
     */
    public function setViewDefinition(ViewDefinition $viewDefinition)
    {
        if ($this->viewDefinition) {
            throw new ConfigurationError("you are overriding an already set view definition");
        }

        $this->viewDefinition = $viewDefinition;

        return $this;
    }

    /**
     * Get view definition
     *
     * @return ViewDefinition
     */
    public function getViewDefinition()
    {
        if (!$this->viewDefinition) {
            $this->viewDefinition = new ViewDefinition();
        }

        return $this->viewDefinition;
    }

    /**
     * Set datasource
     *
     * @param DatasourceInterface $datasource
     *
     * @return $this
     */
    public function setDatasource(DatasourceInterface $datasource)
    {
        $this->datasource = $datasource;

        return $this;
    }

    /**
     * Get datasource
     *
     * @return DatasourceInterface
     */
    public function getDatasource()
    {
        if (!$this->datasource) {
            throw new \LogicException("Cannot build page without a datasource.");
        }

        return $this->datasource;
    }

    /**
     * Get default template
     *
     * @return string
     */
    private function getDefaultTemplate()
    {
        $viewDefinition = $this->getViewDefinition();
        $templates = $viewDefinition->getTemplates();

        if (empty($templates)) {
            throw new \InvalidArgumentException("page builder has no templates");
        }

        $default = $viewDefinition->getDefaultDisplay();
        if (isset($templates[$default])) {
            return $templates[$default];
        }

        if ($this->debug) {
            trigger_error("page builder has no explicit 'default' template set, using first in array", E_USER_WARNING);
        }

        return reset($templates);
    }

    /**
     * Get template for given display name
     *
     * @param string $displayName
     * @param null|string $fallback
     *
     * @return string
     */
    private function getTemplateFor($displayName = null, $fallback = null)
    {
        if (empty($displayName)) {
            return $this->getDefaultTemplate();
        }

        $templates = $this->getViewDefinition()->getTemplates();

        if (!isset($templates[$displayName])) {
            if ($this->debug) {
                trigger_error(sprintf("%s: display has no associated template, switching to default", $displayName), E_USER_WARNING);
            }

            if ($fallback) {
                return $this->getTemplateFor($fallback);
            }

            return $this->getDefaultTemplate();
        }

        return $templates[$displayName];
    }

    /**
     * Compute an identifier for the current page
     *
     * @return null|string
     */
    public function getId()
    {
        if (!$this->id) {
            return null;
        }

        // @todo do better than that...
        return $this->id;
    }

    /**
     * Proceed to search and fetch state
     *
     * @param Request $request
     *   Incoming request
     *
     * @return PageResult
     */
    public function search(Request $request)
    {
        $event = new PageBuilderEvent($this);
        $this->dispatcher->dispatch(PageBuilder::EVENT_SEARCH, $event);

        // Build query from configuration
        $inputDefinition = $this->getInputDefinition();
        $query = (new QueryFactory())->fromRequest($inputDefinition, $request);

        // Initialize properly datasource then execute
        $datasource = $this->getDatasource();
        $datasource->init($query);
        $items = $datasource->getItems($query);

        // Build allowed filters arrays
        $viewDefinition = $this->getViewDefinition();
        $enabledFilters = [];
        foreach ($datasource->getFilters() as $filter) {
            if ($viewDefinition->isFilterDisplayed($filter->getField())) {
                $enabledFilters[] = $filter;
            }
        }

        return new PageResult($inputDefinition, $query, $items, $datasource->getSorts(), $enabledFilters, []);
    }

    /**
     * Create the page view
     *
     * @param PageResult $result
     *   Page result from the search() method
     * @param array $arguments
     *   Additional arguments for the template, please note they will not
     *   override defaults
     *
     * @return PageView
     */
    public function createPageView(PageResult $result, array $arguments = [])
    {
        $query = $result->getQuery();
        $display = $query->getCurrentDisplay();
        $viewDefinition = $this->getViewDefinition();
        $templates = $viewDefinition->getTemplates();

        // Build display links
        // @todo Do it better...
        $displayLinks = [];
        foreach (array_keys($templates) as $name) {
            switch ($name) {
                case 'grid':
                    $displayIcon = 'th';
                    break;
                default:
                case 'table':
                    $displayIcon = 'th-list';
                    break;
            }
            $displayLinks[] = new Link($name, $query->getRoute(), ['display' => $name] + $query->getRouteParameters(), $display === $name, $displayIcon);
        }

        $arguments = [
            'pageId'        => $this->getId(),
            'itemClass'     => $this->getDatasource()->getItemClass(),
            'result'        => $result,
            'items'         => $result->getItems(),
            'filters'       => $viewDefinition->getEnabledFilters() ? $result->getFilters() : [],
            'visualFilters' => [],
            'sorts'         => $result->getSortCollection(),
            'query'         => $query,
            'display'       => $display,
            'displays'      => $displayLinks,
            'hasPager'      => $viewDefinition->isPagerEnabled() && $this->inputDefinition->isSearchEnabled(),
        ] + $arguments;

        $event = new PageBuilderEvent($this);
        $this->dispatcher->dispatch(PageBuilder::EVENT_VIEW, $event);

        return new PageView($this->twig, $this->getTemplateFor($arguments['display']), $arguments);
    }

    /**
     * Shortcut for controllers
     *
     * @param Request $request
     *   Incoming request
     * @param array $arguments
     *   Additional arguments for the template, please note they will not
     *   override defaults
     *
     * @return string
     */
    public function searchAndRender(Request $request, array $arguments = [])
    {
        return $this->createPageView($this->search($request), $arguments)->render();
    }
}
