<?php

namespace MakinaCorpus\Drupal\Dashboard\Tests;

use MakinaCorpus\Drupal\Dashboard\Page\QueryStringParser;
use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Drupal\Dashboard\Page\PageQuery;

/**
 * Tests the page query parsing
 */
class PageQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests behaviour with search
     */
    public function testWithSearch()
    {
        $search = 'foo:a foo:d foo:f some:other fulltext search';

        $request = new Request([
            'q'       => 'some/path',
            'foo'     => 'a|b|c|d|e',
            'test'    => 'test',
            'bar'     => 'baz',
            'search'  => $search,
        ]);

        $pageQuery = new PageQuery($request, 'search', true, ['foo' => ['b', 'c', 'd', 'e', 'f', 'g'], 'bar' => 'baz']);

        // Test the "all" query
        $all = $pageQuery->getAll();
        $this->assertArrayNotHasKey('q', $all);
        $this->assertArrayHasKey('foo', $all);
        $this->assertArrayHasKey('some', $all);
        // Both are merged, no duplicates, outside of base query is dropped
        $this->assertCount(5, $all['foo']);
        $this->assertContains('b', $all['foo']);
        $this->assertContains('c', $all['foo']);
        $this->assertContains('d', $all['foo']);
        $this->assertContains('e', $all['foo']);
        $this->assertContains('f', $all['foo']);
        // Search only driven query is there
        $this->assertSame(['other'], $all['some']);
        // Search is flattened
        $this->assertSame('fulltext search', $all['search']);

        // Test the "route parameters" query
        $params = $pageQuery->getRouteParameters();
        $this->assertArrayNotHasKey('q', $params);
        $this->assertArrayHasKey('foo', $params);
        $this->assertArrayNotHasKey('some', $params);
        // Both are merged, no duplicates, outside of base query is dropped
        $this->assertCount(4, $params['foo']);
        $this->assertContains('b', $params['foo']);
        $this->assertContains('c', $params['foo']);
        $this->assertContains('d', $params['foo']);
        $this->assertContains('e', $params['foo']);
        // Search is flattened
        $this->assertSame($search, $params['search']);
    }

    /**
     * Tests behaviour without search
     */
    public function testWithoutSearch()
    {
        $search = 'foo:a foo:d foo:f some:other fulltext search';

        $request = new Request([
            'q'       => 'some/path',
            'foo'     => 'a|b|c|d|e',
            'test'    => 'test',
            'bar'     => 'baz',
            'search'  => $search,
        ]);

        $pageQuery = new PageQuery($request, 'search', false, ['foo' => ['b', 'c', 'd', 'e', 'f', 'g'], 'bar' => 'baz']);

        // Test the "all" query
        $all = $pageQuery->getAll();
        $this->assertArrayNotHasKey('q', $all);
        $this->assertArrayNotHasKey('some', $all);
        $this->assertArrayHasKey('foo', $all);
        // Since search is not supposed to be parsed, it is not merged
        $this->assertCount(4, $all['foo']);
        $this->assertContains('b', $all['foo']);
        $this->assertContains('c', $all['foo']);
        $this->assertContains('d', $all['foo']);
        $this->assertContains('e', $all['foo']);
        // Search is flattened, and kept as-is
        $this->assertSame($search, $all['search']);

        // Test the "route parameters" query
        $this->assertSame($all, $pageQuery->getRouteParameters());
    }

    /**
     * Tests query string parser
     */
    public function testQueryStringParser()
    {
        $queryString = 'field1:13 foo:"bar baz" bar:2 innner:"this one has:inside" full text bar:test bar:bar not:""';

        $parsed = (new QueryStringParser())->parse($queryString, 's');

        $this->assertCount(1, $parsed['field1']);
        $this->assertSame('13', $parsed['field1'][0]);

        $this->assertCount(1, $parsed['foo']);
        $this->assertSame('bar baz', $parsed['foo'][0]);

        $this->assertCount(3, $parsed['bar']);
        $this->assertSame('2', $parsed['bar'][0]);
        $this->assertSame('test', $parsed['bar'][1]);
        $this->assertSame('bar', $parsed['bar'][2]);

        $this->assertArrayNotHasKey('has', $parsed);
        $this->assertCount(1, $parsed['innner']);
        $this->assertSame('this one has:inside', $parsed['innner'][0]);

        $this->assertArrayNotHasKey('not', $parsed);

        $this->assertCount(2, $parsed['s']);
        $this->assertSame('full', $parsed['s'][0]);
        $this->assertSame('text', $parsed['s'][1]);
    }
}
