<?php

namespace MakinaCorpus\Calista\View\Stream;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Calista\View\AbstractView;
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\PropertyView;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Uses a view definition and proceed to an html page display via Twig
 */
class CsvStreamView extends AbstractView
{
    private $propertyRenderer;

    /**
     * Default constructor
     *
     * @param PropertyRenderer $propertyRenderer
     */
    public function __construct(PropertyRenderer $propertyRenderer)
    {
        $this->propertyRenderer = $propertyRenderer;
    }

    /**
     * Create header row
     *
     * @param DatasourceResultInterface $items
     * @param ViewDefinition $viewDefinition
     * @param PropertyView[] $properties
     *
     * @return string[]
     */
    private function createHeaderRow(DatasourceResultInterface $items, ViewDefinition $viewDefinition, array $properties)
    {
        $ret = [];

        foreach ($properties as $property) {
            $ret[] = $property->getLabel();
        }

        return $ret;
    }

    /**
     * Create item row
     *
     * @param DatasourceResultInterface $items
     * @param ViewDefinition $viewDefinition
     * @param PropertyView[] $properties
     * @param mixed $current
     *
     * @return string[]
     */
    private function createItemRow(DatasourceResultInterface $items, ViewDefinition $viewDefinition, array $properties, $current)
    {
        $ret = [];

        foreach ($properties as $property) {
            $ret[] = $this->propertyRenderer->renderItemProperty($current, $property);
        }

        return $ret;
    }

    /**
     * Render in stream
     *
     * @param ViewDefinition $viewDefinition
     * @param DatasourceResultInterface $items
     * @param Query $query
     * @param resource $resource
     *
     * @return string
     */
    private function renderInStream(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, $resource)
    {
        // Add the BOM for Excel to read correctly the file
        // @todo make this configurable
        fwrite($resource, "\xEF\xBB\xBF");

        // @todo make separator and enclose parameters configurable
        $properties = $this->normalizeProperties($viewDefinition, $items->getItemClass());
        $delimiter = ',';
        $enclosure = '"';
        $escape = '\\';

        // @todo header configurable
        if (true) {
            fputcsv($resource, $this->createHeaderRow($items, $viewDefinition, $properties), $delimiter, $enclosure, $escape);
        }

        foreach ($items as $item) {
            fputcsv($resource, $this->createItemRow($items, $viewDefinition, $properties, $item), $delimiter, $enclosure, $escape);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query)
    {
        ob_start();

        $resource = fopen('php://output', 'w+');
        $this->renderInStream($viewDefinition, $items, $query, $resource);
        fclose($resource);

        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function renderAsResponse(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query)
    {
        $response = new StreamedResponse();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

        $response->setCallback(function () use ($viewDefinition, $items, $query) {
            $resource = fopen('php://output', 'w+');
            $this->renderInStream($viewDefinition, $items, $query, $resource);
            fclose($resource);
        });

        return $response;
    }
}
