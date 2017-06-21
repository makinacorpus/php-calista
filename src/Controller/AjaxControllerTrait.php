<?php

namespace MakinaCorpus\Dashboard\Controller;

use MakinaCorpus\Dashboard\View\Html\TwigView;
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
     * @return TwigView
     */
    private function getTwigViewOrDie(Request $request)
    {
        $pageId = $request->get('name');
        $page = null;

        if (!$pageId) {
            throw new NotFoundHttpException('Not Found');
        }

        try {
            $page = $this->getWidgetFactory()->createTwigView($pageId, $request);
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
        $view = $this->getTwigViewOrDie($request);
        $page = $view->createView($request);

        return new JsonResponse([
            // @todo this is ugly, find a better way
            'query' => $page->getArguments()['query']->all(),
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
