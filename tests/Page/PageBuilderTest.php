<?php

namespace MakinaCorpus\Dashboard\Tests\Page;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\FormPageBuilder;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageResult;
use MakinaCorpus\Dashboard\Page\SortCollection;
use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Dashboard\Tests\Mock\FooPageDefinition;
use MakinaCorpus\Dashboard\Tests\Mock\IntArrayDatasource;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page builder
 */
class PageBuilderTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Tests the page builder factory, very basic tests
     */
    public function testPageBuilderFactory()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        $factory = $container->get('udashboard.page_builder_factory');
        $request = new Request();

        // Now ensures that we can find our definition
        $builder = $factory->createPageBuilder('_test_page_definition', $request);
        $this->assertInstanceOf(PageBuilder::class, $builder);
        $this->assertSame('_test_page_definition', $builder->getId());
        $this->assertInstanceOf(IntArrayDatasource::class, $builder->getDatasource());
        $this->assertSame('_limit', $builder->getConfiguration()->getLimitParameter());

        // And by identifier, and ensure the identifier is not the same as
        // the service identifier, but the one we added in the tag
        $builder = $factory->createPageBuilder('int_array_page', $request);
        $this->assertInstanceOf(PageBuilder::class, $builder);
        $this->assertSame('int_array_page', $builder->getId());
        $this->assertInstanceOf(IntArrayDatasource::class, $builder->getDatasource());
        $this->assertSame('_limit', $builder->getConfiguration()->getLimitParameter());

        // And by class
        $builder = $factory->createPageBuilder(FooPageDefinition::class, $request);
        $this->assertSame(FooPageDefinition::class, $builder->getId());
        $this->assertInstanceOf(PageBuilder::class, $builder);
        $this->assertInstanceOf(IntArrayDatasource::class, $builder->getDatasource());
        $this->assertSame('_limit', $builder->getConfiguration()->getLimitParameter());

        // Ensure we have some stuff that do not work
        try {
            $factory->createFormPageBuilder('_test_datasource', $request);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $factory->createFormPageBuilder(IntArrayDatasource::class, $request);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $factory->createFormPageBuilder('I DO NOT EXIST', $request);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // And empty builder creation
        $builder = $factory->createPageBuilder();
        $this->assertInstanceOf(PageBuilder::class, $builder);
        // Which cannot have a datasource
        try {
            $builder->getDatasource();
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

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

        $pageBuilder = new PageBuilder($this->createTwigEnv(), new EventDispatcher());
        $pageBuilder
            ->setDatasource(new IntArrayDatasource())
            ->setAllowedTemplates([
                'page' => 'module:udashboard:views/Page/page.html.twig',
            ])
            ->setDefaultDisplay('page')
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
        $rendered = $pageView->render();
    }

    /**
     * Tests basics
     */
    public function testDynamicTablePageTemplate()
    {
        // We will test the action extension at the same time
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $configuration = new Configuration(['limit_default' => 7]);

        $pageBuilder = new PageBuilder($container->get('twig'), new EventDispatcher());
        $pageBuilder
            ->setDatasource(new IntArrayDatasource())
            ->setAllowedTemplates([
                'page' => 'module:udashboard:views/Page/page-dynamic-table.html.twig',
            ])
            ->setDefaultDisplay('page')
            ->setConfiguration($configuration)
            ->enableFilter('odd_or_even')
            ->enableVisualFilter('mod3')
        ;

        // Build a page, for fun
        echo $rendered = $pageBuilder->searchAndRender($request);
    }

    /**
     * Basic testing for FormPageBuilder coverage, later will be more advanced tests
     */
    public function testFormPageBuilder()
    {
        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $configuration = new Configuration(['limit_default' => 7]);

        $pageBuilder = new FormPageBuilder($this->createTwigEnv(), new EventDispatcher(), $this->createFormFactory());
        $pageBuilder
            ->setDatasource(new IntArrayDatasource())
            ->setAllowedTemplates([
                'page' => 'module:udashboard:views/Page/page.html.twig',
            ])
            ->setDefaultDisplay('page')
            ->setConfiguration($configuration)
            ->enableFilter('odd_or_even')
            ->enableVisualFilter('mod3')
            ->handleRequest($request)
        ;

        $result = $pageBuilder->search($request);

        // Build a page, for fun
        $pageView = $pageBuilder->createPageView($result);
        $rendered = $pageView->render();
    }
}
