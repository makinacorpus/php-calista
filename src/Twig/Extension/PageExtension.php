<?php

namespace MakinaCorpus\Drupal\Dashboard\Twig\Extension;

use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Dashboard\Page\PageView;
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
        } else if ($page instanceof PageBuilder) {
            return $this->renderPageView($page);
        } else {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'udashboard_page';
    }
}
