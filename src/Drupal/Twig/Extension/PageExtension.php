<?php

namespace MakinaCorpus\Dashboard\Drupal\Twig\Extension;

use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Display pages, considering that 'page' is a variable that points to a
 * PageBuilder instance that was properly setup:
 *
 *   {{ udashboard_page(page) }}
 *
 * Which would be equivalent to:
 *
 *   {{ page.searchAndRender(app.request) }}
 */
class PageExtension extends \Twig_Extension
{
    private $requestStack;

    /**
     * Default constructor
     *
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('udashboard_page', [$this, 'renderPage'], ['is_safe' => ['html']]),
        ];
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('udashboardFilterDefinition', [$this, 'filterDefinition'], ['is_safe' => ['html']]),
            new \Twig_SimpleFilter('udashboardFilterQuery', [$this, 'filterQuery'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render page builder
     *
     * @param PageBuilder $pageBuilder
     *
     * @return string
     *   Rendered page
     */
    public function renderPageBuilder(PageBuilder $pageBuilder)
    {
        return $pageBuilder->searchAndRender($this->requestStack->getCurrentRequest());
    }

    /**
     * Render page builder
     *
     * @param PageView $pageView
     *
     * @return string
     *   Rendered page
     */
    public function renderPageView(PageView $pageView)
    {
        return $pageView->render();
    }

    /**
     * Render page builder
     *
     * @param PageBuilder|PageView $page
     *
     * @return string
     *   Rendered page
     */
    public function renderPage($page)
    {
        if ($page instanceof PageBuilder) {
            return $this->renderPageBuilder($page);
        } else if ($page instanceof PageView) {
            return $this->renderPageView($page);
        } else {
            return '';
        }
    }

    /**
     * Return a JSON encoded representing the filter definition
     *
     * @param \MakinaCorpus\Dashboard\Page\Filter[] $filters
     * @return string
     */
    public function filterDefinition($filters)
    {
        $definition = [];

        foreach ($filters as $filter) {
            $definition[] = [
                'value'   => $filter->getField(),
                'label'   => $filter->getTitle(),
                'options' => !$filter->isSafe() ?: $filter->getChoicesMap(),
            ];
        }

        return json_encode($definition);
    }

    /**
     * Return a JSON encoded representing the initial filter query
     *
     * @param \MakinaCorpus\Dashboard\Page\Filter[] $filters
     * @param string[] $query
     * @return string
     */
    public function filterQuery($filters, $query)
    {
        $filterQuery = [];

        foreach ($filters as $filter) {
            if (isset($query[$filter->getField()])) {
                $filterQuery[$filter->getField()] = $query[$filter->getField()];
            }
        }

        return json_encode($filterQuery);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'udashboard_page';
    }
}
