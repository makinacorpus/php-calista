<?php

namespace MakinaCorpus\Dashboard\View\Html;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Event\ViewEvent;
use MakinaCorpus\Dashboard\Util\Link;
use MakinaCorpus\Dashboard\View\AbstractView;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses a view definition and proceed to an html page display via Twig
 */
class TwigView extends AbstractView
{
    const EVENT_VIEW = 'view:view';
    const EVENT_SEARCH = 'view:search';

    private $debug = false;
    private $dispatcher;
    private $id;
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
     * Get default template
     *
     * @return string
     */
    private function getDefaultTemplate()
    {
        $templates = $this->viewDefinition->getTemplates();

        if (empty($templates)) {
            throw new \InvalidArgumentException("page builder has no templates");
        }

        $default = $this->viewDefinition->getDefaultDisplay();
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

        $templates = $this->viewDefinition->getTemplates();

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
        return $this->id;
    }

    /**
     * Create template arguments
     *
     * @param DatasourceInterface $datasource
     * @param Query $query
     * @param array $arguments
     *
     * @return array
     */
    protected function createTemplateArguments(DatasourceInterface $datasource, Query $query, array $arguments = [])
    {
        $items = $this->execute($datasource, $query);

        $display = $query->getCurrentDisplay();
        $templates = $this->viewDefinition->getTemplates();

        // Build allowed filters arrays
        $enabledFilters = [];
        foreach ($datasource->getFilters() as $filter) {
            if ($this->viewDefinition->isFilterDisplayed($filter->getField())) {
                $enabledFilters[] = $filter;
            }
        }

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

        return [
            'pageId'        => $this->getId(),
            'input'         => $query->getInputDefinition(),
            'itemClass'     => $items->getItemClass(),
            'items'         => $items,
            'filters'       => $enabledFilters,
            'visualFilters' => [],
            'sorts'         => $datasource->getSorts(),
            'query'         => $query,
            'display'       => $display,
            'displays'      => $displayLinks,
            'hasPager'      => $this->viewDefinition->isPagerEnabled() && $this->inputDefinition->isSearchEnabled(),
        ] + $arguments;
    }

    /**
     * Create the renderer
     *
     * @param DatasourceInterface $datasource
     * @param Query $query
     * @param array $arguments
     *
     * @return TwigRenderer
     */
    public function createRenderer(DatasourceInterface $datasource, Query $query, array $arguments = [])
    {
        $event = new ViewEvent($this);
        $this->dispatcher->dispatch(TwigView::EVENT_VIEW, $event);

        $arguments = $this->createTemplateArguments($datasource, $query, $arguments);

        return new TwigRenderer($this->twig, $this->getTemplateFor($arguments['display']), $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function render(DatasourceInterface $datasource, Query $query)
    {
        return $this->createRenderer($datasource, $query)->render();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(DatasourceInterface $datasource, Query $query)
    {
        return new Response($this->render($datasource, $query));
    }
}
