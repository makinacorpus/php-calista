<?php

namespace MakinaCorpus\Dashboard\Page;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\QueryFactory;
use MakinaCorpus\Dashboard\Event\PageBuilderEvent;
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

    private $baseQuery = [];
    private $configuration;
    private $datasource;
    private $debug = false;
    private $defaultDisplay = 'table';
    private $disabledSorts = [];
    private $dispatcher;
    private $displayFilters = true;
    private $displayPager = true;
    private $displaySearch = true;
    private $displaySort = true;
    private $displayVisualSearch = false;
    private $enabledFilters = [];
    private $enabledVisualFilters = [];
    private $id;
    private $templates = [];
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
     * Set configuration
     *
     * @param Configuration $configuration
     *
     * @return $this
     */
    public function setConfiguration(Configuration $configuration)
    {
        // In most cases, configuration will automatically be created only by
        // setting the default limit, or if you set it manually, which means
        // that overriding it will loose information: better throw an exception
        // here and make the potential user error explicit; it'll save more
        // lives that it will make developpers angry
        if ($this->configuration) {
            throw new \LogicException("you are overriding an already set configuration");
        }

        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Get configuration
     *
     * @return Configuration
     */
    public function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = new Configuration();
        }

        return $this->configuration;
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
     * Set default display
     *
     * @param string $display
     *   Display identifier
     *
     * @return $this
     */
    public function setDefaultDisplay($display)
    {
        $this->defaultDisplay = $display;

        return $this;
    }

    /**
     * Set allowed templates
     *
     * @param string[] $displays
     *
     * @return $this
     */
    public function setAllowedTemplates(array $displays)
    {
        $this->templates = $displays;

        return $this;
    }

    /**
     * Set base query
     *
     * @param array $query
     *
     * @return $this
     */
    public function setBaseQuery(array $query)
    {
        $this->baseQuery = $query;

        return $this;
    }

    /**
     * Add base query parameter
     *
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function addBaseQueryParameter($name, $value)
    {
        $this->baseQuery[$name] = $value;

        return $this;
    }

    /**
     * Enable user filter display
     *
     * This has no effect if datasource don't provide filters
     *
     * @return $this
     */
    public function showFilters()
    {
        $this->displayFilters = true;

        return $this;
    }

    /**
     * Disable user filter display
     *
     * Filters will remain enabled, at least for base query set ones
     *
     * @return $this
     */
    public function hideFilters()
    {
        $this->displayFilters = false;

        return $this;
    }

    /**
     * Add filter from datasource
     *
     * @param string $filter The field from filter
     * @return PageBuilder
     */
    public function enableFilter($filter)
    {
        $this->enabledFilters[$filter] = true;

        return $this;
    }

    /**
     * Remove filter from datasource
     *
     * @param string $filter The field from filter
     * @return PageBuilder
     */
    public function disableFilter($filter)
    {
        unset($this->enabledFilters[$filter]);

        return $this;
    }

    /**
     * Enable pagination
     *
     * @return $this
     */
    public function showPager()
    {
        $this->displayPager = true;

        return $this;
    }

    /**
     * Disable pagination
     *
     * @return $this
     */
    public function hidePager()
    {
        $this->displayPager = false;

        return $this;
    }

    /**
     * Enable user search
     *
     * This has no effect if datasource don't support search
     *
     * @return $this
     */
    public function showSearch()
    {
        $this->displaySearch = true;

        return $this;
    }

    /**
     * Disable user search
     *
     * This will completely disable search
     *
     * @return $this
     */
    public function hideSearch()
    {
        $this->displaySearch = false;
        $this->displayVisualSearch = false;

        return $this;
    }

    /**
     * Set the display of visual search filter.
     *
     * @return PageBuilder
     */
    public function showVisualSearch()
    {
        $this->displaySearch = true;
        $this->displayVisualSearch = true;

        return $this;
    }

    /**
     * Set the display of visual search filter.
     *
     * @return PageBuilder
     */
    public function hideVisualSearch()
    {
        $this->displayVisualSearch = false;

        return $this;
    }

    /**
     * Is visual search filter enabled?
     */
    public function visualSearchIsEnabled()
    {
        return $this->displayVisualSearch;
    }

    /**
     * Add visual filter from datasource
     *
     * @param string $filter The field from filter
     * @return PageBuilder
     */
    public function enableVisualFilter($filter)
    {
        $this->enabledVisualFilters[$filter] = true;

        return $this;
    }

    /**
     * Remove visual filter from datasource
     *
     * @param string $filter The field from filter
     * @return PageBuilder
     */
    public function disableVisualFilter($filter)
    {
        unset($this->enabledVisualFilters[$filter]);

        return $this;
    }

    /**
     * Enable user sorting
     *
     * This has no effect if datasource don't support sorting
     *
     * @return $this
     */
    public function showSort()
    {
        $this->displaySort = true;

        return $this;
    }

    /**
     * Disable user sorting
     *
     * This will completely disable sorting, only default will act
     *
     * @return $this
     */
    public function hideSort()
    {
        $this->displaySort = false;

        return $this;
    }

    /**
     * Disable a sort.
     *
     * Sorts are enabled by default but you can disable some.
     *
     * @param string $sort
     * @return $this
     */
    public function disableSort($sort)
    {
        $this->disabledSorts[] = $sort;

        return $this;
    }

    /**
     * Get default template
     *
     * @return string
     */
    private function getDefaultTemplate()
    {
        if (empty($this->templates)) {
            throw new \InvalidArgumentException("page builder has no templates");
        }

        if (isset($this->templates[$this->defaultDisplay])) {
            return $this->templates[$this->defaultDisplay];
        }

        if ($this->debug) {
            trigger_error("page builder has no explicit 'default' template set, using first in array", E_USER_WARNING);
        }

        return reset($this->templates);
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

        if (!isset($this->templates[$displayName])) {
            if ($this->debug) {
                trigger_error(sprintf("%s: display has no associated template, switching to default", $displayName), E_USER_WARNING);
            }

            if ($fallback) {
                return $this->getTemplateFor($fallback);
            }

            return $this->getDefaultTemplate();
        }

        return $this->templates[$displayName];
    }

    /**
     * Compute an identifier for the current page
     *
     * @return null|string
     */
    private function computeId()
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
     * @param Configuration $configuration
     *   Configuration
     *
     * @return PageResult
     */
    public function search(Request $request)
    {
        $event = new PageBuilderEvent($this);
        $this->dispatcher->dispatch(PageBuilder::EVENT_SEARCH, $event);

        // Build query from configuration
        $configuration = $this->getConfiguration();
        $query = (new QueryFactory())->fromRequest($configuration, $request, $this->baseQuery);

        // Initialize properly datasource then execute
        $datasource = $this->getDatasource();
        $datasource->init($query);
        $items = $datasource->getItems($query);

        // Build allowed filters arrays
        $filters = $visualFilters = [];
        if ($baseFilters = $datasource->getFilters($query)) {
            foreach ($baseFilters as $filter) {
                if (array_key_exists($filter->getField(), $this->enabledFilters)) {
                    $filters[] = $filter;
                }
                if (array_key_exists($filter->getField(), $this->enabledVisualFilters)) {
                    $visualFilters[] = $filter;
                }
            }
        }

        return new PageResult($configuration, $query, $items, $datasource->getSorts($query), $filters, $visualFilters);
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

        // Build display links
        // @todo Do it better...
        $displayLinks = [];
        foreach (array_keys($this->templates) as $name) {
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

        // Build sort links from here

        $arguments = [
            'pageId'        => $this->computeId(),
            'result'        => $result,
            'items'         => $result->getItems(),
            'filters'       => $this->displayFilters ? $result->getFilters() : [],
            'visualFilters' => $this->displayVisualSearch ? $result->getVisualFilters() : [],
            'sorts'         => $result->getSortCollection(),
            'query'         => $query,
            'display'       => $display,
            'displays'      => $displayLinks,
            'hasPager'      => $this->displayPager && $this->configuration->isSearchEnabled(),
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
