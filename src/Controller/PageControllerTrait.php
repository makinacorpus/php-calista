<?php

namespace MakinaCorpus\Calista\Controller;

use MakinaCorpus\Calista\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\Util\AdminTable;
use MakinaCorpus\Calista\View\ViewInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use MakinaCorpus\Calista\DependencyInjection\PageDefinitionInterface;

/**
 * Gives a few helper methods for retrieving and rendering views and pagess.
 */
trait PageControllerTrait
{
    /**
     * Gets a container service by its id.
     *
     * @param string $id
     *
     * @return object
     */
    // protected abstract function get($id);

    /**
     * Escape string for HTML
     *
     * @param string $string
     *
     * @return string
     */
    private function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Create datasource from request
     *
     * @param Request $request
     *
     * @return ViewInterface
     */
    protected function getViewOrDie(Request $request)
    {
        $pageId = $request->get('_page_id');
        $page = null;

        if (!$pageId) {
            throw new NotFoundHttpException('Not Found');
        }

        try {
            $page = $this->getViewFactory()->getView($pageId);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Not Found', $e);
        } catch (ServiceNotFoundException $e) {
            throw new NotFoundHttpException('Not Found', $e);
        }

        return $page;
    }

    /**
     * Get page factory
     *
     * @return ViewFactory
     */
    protected function getViewFactory()
    {
        return $this->get('calista.view_factory');
    }

    /**
     * Get page renderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return $this->get('calista.page_renderer');
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
    protected function renderPage($name, Request $request, array $inputOptions = [])
    {
        return $this->getPageRenderer()->renderPage($name, $request, $inputOptions);
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
    protected function renderPageResponse($name, Request $request, array $inputOptions = [])
    {
        return $this->getPageRenderer()->renderPageResponse($name, $request, $inputOptions);
    }

    /**
     * Create an admin table
     *
     * @param string $name
     *   Name will be the template suggestion, and the event name, where the
     *   event name will be admin:table:NAME
     * @param mixed $attributes
     *   Arbitrary table attributes that will be stored into the table
     *
     * @return AdminTable
     */
    protected function createAdminTable($name, array $attributes = [])
    {
        return new AdminTable($name, $attributes, $this->get('event_dispatcher'));
    }

    /**
     * Given some admin table, abitrary add a new section with attributes within
     *
     * @param AdminTable $table
     *   Table in which to add those
     * @param mixed[] $attributes
     *   Attributes to display in the table, keys will be the row labels and
     *   values can be anything, that will be converted to json for display
     *   if not scalar values
     * @param string $title
     *   Section title
     */
    protected function addArbitraryAttributesToTable(AdminTable $table, array $attributes = [], $title = null)
    {
        if (!$attributes) {
            return;
        }

        if (!$title) {
            $title = "Attributes";
        }

        $table->addHeader($title, 'attributes');

        foreach ($attributes as $key => $value) {

            if (is_scalar($value)) {
                $value = $this->escape($value);
            } else {
                $value = '<pre>' . json_encode($value, JSON_PRETTY_PRINT) . '</pre>';
            }

            $table->addRow($this->escape($key), $value, $key);
        }
    }
}
