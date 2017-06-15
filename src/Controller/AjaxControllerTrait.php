<?php

namespace MakinaCorpus\Dashboard\Controller;

use MakinaCorpus\Dashboard\Page\PageBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * AJAX controller for page builders, this is both suitable for the fullstack
 * Symfony's controller and makinacorpus/drupal-sf-dic degraded controller that
 * carries the same signature that Symfony's one.
 */
trait AjaxControllerTrait
{
    use PageControllerTrait;

    /**
     * Create datasource from request
     *
     * @param Request $request
     *
     * @return PageBuilder
     */
    private function getPageBuilderOrDie(Request $request)
    {
        $pageId = $request->get('name');
        $page = null;

        if (!$pageId) {
            throw new NotFoundHttpException('Not Found');
        }

        try {
            $page = $this->createPageBuilder($pageId, $request);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Not Found', $e);
        } catch (ServiceNotFoundException $e) {
            throw new NotFoundHttpException('Not Found', $e);
        }

        return $page;
    }

    /**
     * Type search action
     */
    public function searchAction(Request $request)
    {
        return $this->refreshAction($request);
    }

    /**
     * Refresh everything
     */
    public function refreshAction(Request $request)
    {
        $builder  = $this->getPageBuilderOrDie($request);
        $result   = $builder->search($request);
        $page     = $builder->createPageView($result);

        return new JsonResponse([
            'query' => $result->getQuery()->all(),
            'blocks' => [
                'filters'       => $page->renderPartial('filters'),
                'display_mode'  => $page->renderPartial('display_mode'),
                'sort_links'    => $page->renderPartial('sort_links'),
                'item_list'     => $page->renderPartial('item_list'),
                'pager'         => $page->renderPartial('pager'),
            ],
        ]);
    }
}
