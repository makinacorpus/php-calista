<?php

namespace MakinaCorpus\Calista\Controller;

use MakinaCorpus\Calista\View\Html\TwigView;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * AJAX controller for HTML pages, this is both suitable for the fullstack
 * Symfony's controller and makinacorpus/drupal-sf-dic degraded controller that
 * carries the same signature that Symfony's one.
 */
class AjaxController implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    use PageControllerTrait;

    /**
     * {@inheritdoc}
     */
    protected function get($id)
    {
        return $this->container->get($id);
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
        try {
            // Fetch data from session
            $sessionData = $this->getPageRenderer()->getSessionData($request);
            $pageId = $sessionData['name'];
            $inputOptionsOverride = $sessionData['input'];

            // Prepare all objects
            $factory = $this->getViewFactory();
            $page = $factory->getPageDefinition($pageId);
            $viewDefinition = $page->getViewDefinition();
            $view = $factory->getView($viewDefinition->getViewType());

        } catch (\Exception $e) {
            throw new NotFoundHttpException('Not Found', $e);
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
