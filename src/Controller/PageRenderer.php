<?php

namespace MakinaCorpus\Calista\Controller;

use MakinaCorpus\Calista\DependencyInjection\PageDefinitionInterface;
use MakinaCorpus\Calista\DependencyInjection\ViewFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Yes, it renders pages.
 */
class PageRenderer
{
    private $viewFactory;

    /**
     * Default constructor
     *
     * @param ViewFactory $viewFactory
     */
    public function __construct(ViewFactory $viewFactory)
    {
        $this->viewFactory = $viewFactory;
    }

    /**
     * Prepare session
     *
     * @param string $pageId
     * @param Request $request
     * @param array $inputOptions
     *
     * @return string
     */
    private function prepareSession($pageId, Request $request, array $inputOptions = [])
    {
        $session = $request->getSession();

        if ($session) {
            // View must inherit from the page definition identifier to ensure
            // that AJAX queries will work
            if ($inputOptions) {
                $pageToken = $pageId . md5(serialize($inputOptions));
            } else {
                $pageToken = $pageId;
            }

            $session->set('calista-' . $pageToken, [
                'name' => $pageId,
                'input' => $inputOptions,
            ]);

            return $pageToken;
        }
    }

    /**
     * Get page identifier from session
     *
     * @param Request $request
     * @param string $parameterName
     *
     * @return array
     *   'name' key contains the real page identifier
     *   'input' key contains the input arguments override given when page was spawned
     */
    public function getSessionData(Request $request, $parameterName = '_page_id')
    {
        $pageToken = $request->get($parameterName);
        if (!$pageToken) {
            throw new \RuntimeException("request has no page identifier");
        }

        $session = $request->getSession();
        if (!$session) {
            throw new \RuntimeException("request has no session");
        }
        if (!$session->has('calista-' . $pageToken)) {
            throw new \RuntimeException("there is no session data for given page identifier");
        }

        // Fetch real page identifier and input overrides from session
        $data = $session->get('calista-' . $pageToken);

        if (!is_array($data) || !isset($data['name'])) {
            throw new \RuntimeException("session data is corrupted");
        }

        $data += ['input' => []];

        return $data;
    }

    /**
     * Render a page from definition
     *
     * @param string|PageDefinitionInterface $page
     *   Page class or identifier
     * @param Request $request
     *   Incomming request
     * @param array $inputOptions
     *   Overrides for the input options
     *
     * @return string
     */
    public function renderPage($name, Request $request, array $inputOptions = [])
    {
        if ($name instanceof PageDefinitionInterface) {
            $page = $name;
        } else {
            $page = $this->viewFactory->getPageDefinition($name);
        }

        $viewDefinition = $page->getViewDefinition();
        $view = $this->viewFactory->getView($viewDefinition->getViewType());

        $query = $page->getInputDefinition($inputOptions)->createQueryFromRequest($request);
        $items = $page->getDatasource()->getItems($query);

        if ($pageToken = $this->prepareSession($page->getId(), $request, $inputOptions)) {
            $view->setId($pageToken);
        }

        return $view->render($viewDefinition, $items, $query);
    }

    /**
     * Render a page from definition
     *
     * Using a response for rendering is the right choice when you generate
     * outputs with large datasets, it allows the view to control the response
     * type hence use a streamed response whenever possible.
     *
     * @param string|PageDefinitionInterface $page
     *   Page class or identifier
     * @param Request $request
     *   Incomming request
     * @param array $inputOptions
     *   Overrides for the input options
     *
     * @return Response
     */
    public function renderPageResponse($name, Request $request, array $inputOptions = [])
    {
        if ($name instanceof PageDefinitionInterface) {
            $page = $name;
        } else {
            $page = $this->viewFactory->getPageDefinition($name);
        }

        $viewDefinition = $page->getViewDefinition();
        $view = $this->viewFactory->getView($viewDefinition->getViewType());

        // View must inherit from the page definition identifier to ensure
        // that AJAX queries will work
        if ($pageToken = $this->prepareSession($page->getId(), $request, $inputOptions)) {
            $view->setId($pageToken);
        }

        $query = $page->getInputDefinition($inputOptions)->createQueryFromRequest($request);
        $items = $page->getDatasource()->getItems($query);

        return $view->renderAsResponse($viewDefinition, $items, $query);
    }
}
