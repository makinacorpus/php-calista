<?php

namespace MakinaCorpus\Dashboard\View\Html;

use MakinaCorpus\Dashboard\Datasource\DatasourceResultInterface;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Error\ConfigurationError;
use MakinaCorpus\Dashboard\Event\ViewEvent;
use MakinaCorpus\Dashboard\Util\Link;
use MakinaCorpus\Dashboard\View\AbstractView;
use MakinaCorpus\Dashboard\View\ViewDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses a view definition and proceed to an html page display via Twig
 */
class TwigView extends AbstractView
{
    private $debug = false;
    private $dispatcher;
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
     * Get templates from definition
     *
     * @param ViewDefinition $viewDefinition
     *
     * @return string[]
     */
    private function getTemplates(ViewDefinition $viewDefinition)
    {
        $templates = $viewDefinition->getTemplates();

        if (!$templates) {
            $templates = ['list' => 'module:udashboard:views/Page/page-dynamic-table.html.twig'];
        }

        return $templates;
    }

    /**
     * Get template for given display name
     *
     * @param string $displayName
     * @param null|string $fallback
     *
     * @return string
     */
    private function getTemplateFor(ViewDefinition $viewDefinition, $displayName = null)
    {
        $templates = $this->getTemplates($viewDefinition);

        if (!isset($templates[$displayName])) {
            return key($templates);
        }

        return $templates[$displayName];
    }

    /**
     * Create template arguments
     *
     * @param ViewDefinition $viewDefinition
     * @param DatasourceResultInterface $items
     * @param Query $query
     *
     * @return array
     */
    protected function createTemplateArguments(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, array $arguments = [])
    {
        $inputDefinition = $query->getInputDefinition();
        $display = $query->getCurrentDisplay();
        $templates = $this->getTemplates($viewDefinition);

        // Find the right display to use, never let the variable empty
        if (!$display) {
            $display = $viewDefinition->getDefaultDisplay();
            if (!$display) {
                $display = key($templates);
            }
        }

        // Build allowed filters arrays
        $enabledFilters = [];
        foreach ($inputDefinition->getFilters() as $filter) {
            if ($viewDefinition->isFilterDisplayed($filter->getField())) {
                $enabledFilters[] = $filter;
            }
        }

        // Build display links
        // @todo Do it better...
        $displayLinks = [];
        $routeParameters = $query->getRouteParameters();
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
            if ($name === $viewDefinition->getDefaultDisplay()) {
                $displayLinks[] = new Link($name, $query->getRoute(), array_diff_key($routeParameters, ['display' => '']), $display === $name, $displayIcon);
            } else {
                $displayLinks[] = new Link($name, $query->getRoute(), ['display' => $name] + $routeParameters, $display === $name, $displayIcon);
            }
        }

        return [
            'pageId'        => $this->getId(),
            'input'         => $inputDefinition,
            'definition'    => $viewDefinition,
            'itemClass'     => $items->getItemClass(),
            'items'         => $items,
            'filters'       => $enabledFilters,
            'visualFilters' => [],
            'sorts'         => $inputDefinition->getAllowedSorts(),
            'query'         => $query,
            'display'       => $display,
            'displays'      => $displayLinks,
            'hasPager'      => $viewDefinition->isPagerEnabled() && $inputDefinition->isSearchEnabled(),
        ] + $arguments;
    }

    /**
     * Create the renderer
     *
     * @param ViewDefinition $viewDefinition
     * @param DatasourceResultInterface $items
     * @param Query $query
     *
     * @return TwigRenderer
     */
    public function createRenderer(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, array $arguments = [])
    {
        $event = new ViewEvent($this);
        $this->dispatcher->dispatch(ViewEvent::EVENT_VIEW, $event);

        $arguments = $this->createTemplateArguments($viewDefinition, $items, $query, $arguments);

        return new TwigRenderer($this->twig, $this->getTemplateFor($viewDefinition, $arguments['display']), $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query)
    {
        return $this->createRenderer($viewDefinition, $items, $query)->render();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query)
    {
        return new Response($this->render($viewDefinition, $items, $query));
    }
}
