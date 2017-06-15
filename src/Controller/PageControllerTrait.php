<?php

namespace MakinaCorpus\Dashboard\Controller;

use MakinaCorpus\Dashboard\Datasource\DatasourceInterface;
use MakinaCorpus\Dashboard\Drupal\Table\AdminTable;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageBuilderFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives a few helper methods for retrieving page builders.
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
    protected abstract function get($id);

    /**
     * Renders a view.
     *
     * @param string $view
     * @param array $parameters
     * @param Response $response
     *
     * @return Response
     */
    protected abstract function render($view, array $parameters = [], Response $response = null);

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
     * Get page factory
     *
     * @return PageBuilderFactory
     */
    protected function getWidgetFactory()
    {
        return $this->get('udashboard.page_builder_factory');
    }

    /**
     * Create a page builder with or without type
     *
     * @param string $name
     *   If given will use the given page type
     * @param Request $request
     *   Mandatory when name is given
     *
     * @return PageBuilder
     */
    protected function createPageBuilder($name = null, Request $request = null)
    {
        return $this->getWidgetFactory()->createPageBuilder($name, $request);
    }

    /**
     * Render page
     */
    protected function renderPage(Request $request, DatasourceInterface $datasource, $templateName = null, array $arguments = [])
    {
        // @todo This will work in Drupal, but not in Symfony since it does
        //   not return a real Response object, but a string instead
        return $this
            ->createPageBuilder()
            ->setAllowedTemplates(['default' => $templateName])
            ->searchAndRender($request, $arguments)
        ;
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
