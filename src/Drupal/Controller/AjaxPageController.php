<?php

namespace MakinaCorpus\Dashboard\Drupal\Controller;

use MakinaCorpus\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\Page\NodeAdminPageInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxPageController extends Controller
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
            throw $this->createNotFoundException();
        }

        try {
            $page = $this->getPageBuilder($pageId, $request);
        } catch (\InvalidArgumentException $e) {
            throw $this->createNotFoundException();
        } catch (ServiceNotFoundException $e) {
            throw $this->createNotFoundException();
        }

        $account = $this->getUser();
        // @todo move the interface in the dashboard module
        if ($page instanceof NodeAdminPageInterface) {
            if (!$page->userIsGranted($account)) {
                throw $this->createAccessDeniedException();
            }
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
        $pageType = $this->getPageBuilderOrDie($request);
        $result   = $pageType->search($request);
        $page     = $pageType->createPageView($result);

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
