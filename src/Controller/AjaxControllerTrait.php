<?php

namespace MakinaCorpus\Calista\Controller;

use MakinaCorpus\Calista\View\Html\TwigView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * AJAX controller for HTML pages, this is both suitable for the fullstack
 * Symfony's controller and makinacorpus/drupal-sf-dic degraded controller that
 * carries the same signature that Symfony's one.
 */
trait AjaxControllerTrait
{
    use PageControllerTrait;

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
        $pageToken = $request->get('_page_id');
        if (!$pageToken) {
            throw new NotFoundHttpException("Not Found");
        }

        $session = $request->getSession();
        if (!$session) {
            throw new NotFoundHttpException("Not Found");
        }
        if (!$session->has('calista-' . $pageToken)) {
            throw new NotFoundHttpException("Not Found");
        }

        // Fetch real page identifier and input overrides from session
        $sessionData = $session->get('calista-' . $pageToken);
        $pageId = $sessionData['name'];
        $inputOptionsOverride = $sessionData['input'];

        try {
            // Prepare all objects
            $factory = $this->getViewFactory();
            $page = $factory->getPageDefinition($pageId);
            $viewDefinition = $page->getViewDefinition();
            $view = $factory->getView($viewDefinition->getViewType());
        } catch (ServiceNotFoundException $e) {
            throw new NotFoundHttpException('Not Found');
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Not Found');
        }

        if (!$view instanceof TwigView) {
            throw new NotFoundHttpException('Not Found');
        }

        // Clone request with original parameters so that AJAX rendered links
        // don't get modified by the AJAX callback route.
        $subRequest = $request->duplicate();
        if ($route = $request->query->get('_route')) {
            $subRequest->attributes->set('_route', $route);
        }
        $subRequest->query->remove('_page_id');
        $subRequest->query->remove('_route');
        // Anti-cache token from jQuery
        $subRequest->query->remove('_');

        /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $requestStack->push($subRequest);

        try {
            $query = $page->getInputDefinition($inputOptionsOverride)->createQueryFromRequest($subRequest);
            $items = $page->getDatasource()->getItems($query);
            $renderer = $view->createRenderer($viewDefinition, $items, $query);

            $response = new JsonResponse([
                'query' => $query->getRouteParameters(),
                'blocks' => [
                    'filters'       => $renderer->renderPartial('filters'),
                    'display_mode'  => $renderer->renderPartial('display_mode'),
                    'sort_links'    => $renderer->renderPartial('sort_links'),
                    'item_list'     => $renderer->renderPartial('item_list'),
                    'pager'         => $renderer->renderPartial('pager'),
                ],
            ]);
        } catch (\Exception $e) {
            throw $e;
        } finally {
            $requestStack->pop();
        }

        return $response;
    }
}
