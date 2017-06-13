<?php

namespace MakinaCorpus\Dashboard\Page\Tests;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Datasource\QueryFactory;
use MakinaCorpus\Dashboard\Page\SortCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page query parsing
 */
class SortCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests basics
     */
    public function testBasics()
    {
        $sortCollection = new SortCollection(
            [
                'a' => 'The A sort',
                'b' => 'The B sort',
                'c' => 'The C sort',
            ],
            null,
            Query::SORT_DESC
        );

        $this->assertSame(3, count($sortCollection));

        $request = new Request(['st' => 'b', 'by' => 'asc', 'foo' => 'barr'], [], ['_route' => 'my_route']);
        $query = (new QueryFactory())->fromRequest(new Configuration(), $request);

        $links = $sortCollection->getFieldLinks($query);
        $this->assertCount(3, $links);

        // Ensure links consistency, b disabled
        $this->assertSame('The A sort', $links[0]->getTitle());
        $this->assertFalse($links[0]->isActive());
        // Links 1 (b) is current sort
        $this->assertSame('The B sort', $links[1]->getTitle());
        $this->assertTrue($links[1]->isActive());
        // c disabled
        $this->assertSame('The C sort', $links[2]->getTitle());
        $this->assertFalse($links[2]->isActive());

        // Default sort should not have 'st' parameter
        $this->assertArrayNotHasKey('st', $links[0]->getRouteParameters());

        // Just check with one, but check for arguments, route, etc...
        $routeParameters = $links[1]->getRouteParameters();
        // By is there, default is desc, we asked for asc
        $this->assertArrayHasKey('by', $routeParameters);
        $this->assertArrayHasKey('st', $routeParameters);
        $this->assertSame('b', $routeParameters['st']);
        $this->assertSame('asc', $routeParameters['by']);
        $this->assertSame('barr', $routeParameters['foo']);

        $links = $sortCollection->getOrderLinks($query);
        $this->assertCount(2, $links);
    }
}
