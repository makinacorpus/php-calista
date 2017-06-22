<?php

namespace MakinaCorpus\Dashboard\View\Html;

use MakinaCorpus\Dashboard\Datasource\DatasourceResultInterface;
use MakinaCorpus\Dashboard\Datasource\Query;
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
     * Get default template
     *
     * @return string
     */
    private function getDefaultTemplate(ViewDefinition $viewDefinition)
    {
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
    private function getTemplateFor(ViewDefinition $viewDefinition, $displayName = null, $fallback = null)
    {
        if (empty($displayName)) {
            return $this->getDefaultTemplate($viewDefinition);
        }

        $templates = $viewDefinition->getTemplates();

        if (!isset($templates[$displayName])) {
            if ($this->debug) {
                trigger_error(sprintf("%s: display has no associated template, switching to default", $displayName), E_USER_WARNING);
            }

            if ($fallback) {
                return $this->getTemplateFor($fallback);
            }

            return $this->getDefaultTemplate($viewDefinition);
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
        $templates = $viewDefinition->getTemplates();

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
            'input'         => $inputDefinition,
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
