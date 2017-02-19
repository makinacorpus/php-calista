<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

/**
 * @todo
 *   - Remove buiseness methods from this oibject and move them to "Page"
 *   - widget factory should return a page, not a builder
 */
final class PageBuilder
{
    const DEFAULT_LIMIT = 24;

    private $id;
    private $twig;
    private $debug = false;
    private $defaultDisplay = 'table';
    private $displayFilters = true;
    private $displayPager = true;
    private $displaySearch = true;
    private $displaySort = true;
    private $datasource;
    private $templates = [];
    private $baseQuery = [];

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string[] $displays
     * @param string $defaultDisplay
     *   Default template
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->debug = $twig->isDebug();
    }

    /**
     * Set builder identifier
     *
     * @param string $id
     */
    public function setId($id)
    {
        if ($this->id && $this->id !== $id) {
            throw new \LogicException("cannot change a page builder identifier");
        }

        $this->id = $id;
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
            throw new \LogicException("cannot build page without a datasource");
        }

        return $this->datasource;
    }

    /**
     * Set default display
     *
     * @param string $defaultDisplay
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
     *   Incomming request
     *
     * @return PageResult
     */
    public function search(Request $request)
    {
        $datasource = $this->getDatasource();

        $route = $request->attributes->get('_route');
        $state = new PageState();

        $query = array_merge(
            $request->query->all(),
            $request->attributes->get('_route_params', [])
        );

        // We are working with Drupal, q should never get here.
        unset($query['q']);
        $query = array_filter($query, function ($value) {
            return $value !== '' && $value !== null;
        });

        $query = Filter::fixQuery($query); // @todo this is ugly

        // Ensure that query values are in base query bounds
        // @todo find a more generic and proper way to do this
        foreach ($this->baseQuery as $name => $allowed) {
            if (isset($query[$name])) {
                $input = $query[$name];
                // Normalize
                if (!is_array($allowed)) {
                    $allowed = [$allowed];
                }
                if (!is_array($input)) {
                    $input = [$input];
                }
                // Restrict to fixed bounds
                $query[$name] = array_intersect($input, $allowed);
            }
        }

        $datasource->init($query, $this->baseQuery);

        $sort = new SortManager();
        $sort->prepare($route, $query);

        if ($sortFields = $datasource->getSortFields($query)) {
            $sort->setFields($sortFields);
            // Do not set the sort order links if there is no field to sort on
            if ($sortDefault = $datasource->getDefaultSort()) {
                $sort->setDefault(...$sortDefault);
            }
            // Enfore sorts not being displayed
            if (!$this->displaySort) {
                $sort->setFields([$sortDefault[0] => 'default']);
            }
        }

        // Build the page state gracefully, this uglyfies the code but it does
        // help to reduce code within the datasources
        $state->setSortField($sort->getCurrentField($query));
        $state->setSortOrder($sort->getCurrentOrder($query));
        if (!$this->displayPager || empty($query[$state->getPageParameter()])) {
            $state->setRange(self::DEFAULT_LIMIT);
        } else {
            $state->setRange(self::DEFAULT_LIMIT, $query[$state->getPageParameter()]);
        }

        if ($this->displaySearch && $datasource->hasSearchForm()) {
            // Same with search parameter and value
            $searchParameter = $datasource->getSearchFormParamName();
            $state->setSearchParameter($searchParameter);
            $state->setCurrentSearch($request->get($searchParameter));
            if (!empty($query[$searchParameter])) {
                $state->setCurrentSearch($query[$searchParameter]);
            }
        }

        $items = $datasource->getItems(array_merge($query, $this->baseQuery), $state);

        $filters = $datasource->getFilters($query);
        if ($filters) {
            foreach ($filters as $index => $filter) {
                if (isset($this->baseQuery[$filter->getField()])) {
                    //unset($filters[$index]);
                }
                $filter->prepare($route, $query);
            }
        } else { // Avoid possibly broken implementations
            $filters = [];
        }

        // Set current display
        $state->setCurrentDisplay($request->get('display'));

        // Disable virtually the pager
        if (!$this->displayPager) {
            $state->setTotalItemCount(null);
        }

        return new PageResult($route, $state, $items, $query, $filters, $sort);
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
        $state = $result->getState();

        $display = $state->getCurrentDisplay();
        if (!$display) {
            $state->setCurrentDisplay($display = $this->defaultDisplay);
        }

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
            $displayLinks[] = new Link($name, $result->getRoute(), ['display' => $name] + $result->getQuery(), $display === $name, $displayIcon);
        }

        $arguments = [
            'pageId'    => $this->computeId(),
            'result'    => $result,
            'state'     => $state,
            'route'     => $result->getRoute(),
            'filters'   => $this->displayFilters ? $result->getFilters() : [],
            'display'   => $display,
            'displays'  => $displayLinks,
            'query'     => $result->getQuery(),
            'sort'      => $result->getSort(),
            'items'     => $result->getItems(),
        ] + $arguments;

        return new PageView($this->twig, $this->getTemplateFor($arguments['display']), $arguments);
    }

    /**
     * Shortcut for controllers
     *
     * @param Request $request
     *
     * @return string
     */
    public function searchAndRender(Request $request)
    {
        return $this->createPageView($this->search($request))->render();
    }
}
