<?php

namespace MakinaCorpus\Dashboard\Tests\Page;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageResult;
use MakinaCorpus\Dashboard\Page\SortCollection;
use MakinaCorpus\Dashboard\Tests\Mock\IntArrayDatasource;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page builder
 */
class PageBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests basics
     */
    public function testBasics()
    {
        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $configuration = new Configuration(['limit_default' => 7]);

        $pageBuilder = new PageBuilder(new \Twig_Environment(new \Twig_Loader_Filesystem()), new EventDispatcher());
        $pageBuilder
            ->setDatasource(new IntArrayDatasource())
            ->setConfiguration($configuration)
            ->enableFilter('odd_or_even')
            ->enableVisualFilter('mod3')
        ;

        $result = $pageBuilder->search($request);
        $this->assertInstanceOf(PageResult::class, $result);
        $this->assertSame($configuration, $pageBuilder->getConfiguration());

        // Ensure filters etc
        $filters = $result->getFilters();
        $this->assertSame('odd_or_even', reset($filters)->getField());
        $this->assertSame('Odd or Even', reset($filters)->getTitle());
        $visualFilters = $result->getVisualFilters();
        $this->assertSame('mod3', reset($visualFilters)->getField());
        $this->assertSame('Modulo 3', reset($visualFilters)->getTitle());

        $items = $result->getItems();
        $query = $result->getQuery();

        $this->assertCount(7, $items);
        $this->assertSame(3, $query->getPageNumber());
        $this->assertSame(128, $items->getTotalCount());

        // Ensure sorting was OK
        $itemsArray = iterator_to_array($items);
        $this->assertGreaterThan($itemsArray[1], $itemsArray[0]);

        // Is sort collection OK?
        $this->assertInstanceOf(SortCollection::class, $result->getSortCollection());

        // Build a page, for fun
        $pageView = $pageBuilder->createPageView($result);
    }
}
