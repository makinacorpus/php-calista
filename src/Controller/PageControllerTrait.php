<?php

namespace MakinaCorpus\Dashboard\Controller;

use MakinaCorpus\Dashboard\Util\AdminTable;
use MakinaCorpus\Dashboard\View\ViewFactory;

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
     * @return ViewFactory
     */
    protected function getWidgetFactory()
    {
        return $this->get('udashboard.view_factory');
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
