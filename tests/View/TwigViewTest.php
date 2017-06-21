<?php

namespace MakinaCorpus\Dashboard\Tests\View;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\SortCollection;
use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Dashboard\Tests\Mock\FooPageDefinition;
use MakinaCorpus\Dashboard\Tests\Mock\IntArrayDatasource;
use MakinaCorpus\Dashboard\View\Html\FormTwigView;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use MakinaCorpus\Dashboard\View\ViewDefinition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the page builder
 */
class TwigViewTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Tests the page builder factory, very basic tests
     */
    public function testViewFactory()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        /** @var \MakinaCorpus\Dashboard\View\ViewFactory $factory */
        $factory = $container->get('udashboard.view_factory');
        $request = new Request();

        // Now ensures that we can find our definition
        $view = $factory->createTwigView('_test_view', $request);
        $this->assertInstanceOf(TwigView::class, $view);
        $this->assertSame('_test_view', $view->getId());
        $this->assertInstanceOf(IntArrayDatasource::class, $view->getDatasource());
        $this->assertSame('_limit', $view->getInputDefinition()->getLimitParameter());

        // And by identifier, and ensure the identifier is not the same as
        // the service identifier, but the one we added in the tag
        $view = $factory->createTwigView('int_array_page', $request);
        $this->assertInstanceOf(TwigView::class, $view);
        $this->assertSame('int_array_page', $view->getId());
        $this->assertInstanceOf(IntArrayDatasource::class, $view->getDatasource());
        $this->assertSame('_limit', $view->getInputDefinition()->getLimitParameter());

        // And by class
        $view = $factory->createTwigView(FooPageDefinition::class, $request);
        $this->assertSame(FooPageDefinition::class, $view->getId());
        $this->assertInstanceOf(TwigView::class, $view);
        $this->assertInstanceOf(IntArrayDatasource::class, $view->getDatasource());
        $this->assertSame('_limit', $view->getInputDefinition()->getLimitParameter());

        // Ensure we have some stuff that do not work
        try {
            $factory->createFormTwigView('_test_datasource', $request);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $factory->createFormTwigView(IntArrayDatasource::class, $request);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
        try {
            $factory->createFormTwigView('I DO NOT EXIST', $request);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }

        // And empty builder creation
        $view = $factory->createTwigView();
        $this->assertInstanceOf(TwigView::class, $view);
        // Which cannot have a datasource
        try {
            $view->getDatasource();
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

        $datasource = new IntArrayDatasource();
        $inputDefinition = new InputDefinition($datasource, ['limit_default' => 7]);

        $viewDefinition = new ViewDefinition([
            'default_display' => 'page',
            'enabled_filters' => ['odd_or_even'],
            'templates' => [
                'page' => 'module:udashboard:views/Page/page.html.twig',
            ],
        ]);
        $view = new TwigView($this->createTwigEnv(), new EventDispatcher());
        $view
            ->setDatasource($datasource)
            ->setInputDefinition($inputDefinition)
            ->setViewDefinition($viewDefinition)
            //->enableVisualFilter('mod3')
        ;

$this->markTestIncomplete("this needs to be fixed");
        $result = $view->search($request);
        $this->assertInstanceOf(PageResult::class, $result);
        $this->assertSame($inputDefinition, $view->getInputDefinition());

        // Ensure filters etc
        $filters = $result->getFilters();
        $this->assertSame('odd_or_even', reset($filters)->getField());
        $this->assertSame('Odd or Even', reset($filters)->getTitle());
//         $visualFilters = $result->getVisualFilters();
//         $this->assertSame('mod3', reset($visualFilters)->getField());
//         $this->assertSame('Modulo 3', reset($visualFilters)->getTitle());

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
        $renderer = $view->createPageView($result);
        $output = $pageView->render();
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

        $datasource = new IntArrayDatasource();
        $inputDefinition = new InputDefinition($datasource, ['limit_default' => 7]);

        $viewDefinition = new ViewDefinition([
            'default_display' => 'page',
            'enabled_filters' => ['odd_or_even'],
            'templates' => [
                'page' => 'module:udashboard:views/Page/page-dynamic-table.html.twig',
            ],
        ]);

        $view = new TwigView($container->get('twig'), new EventDispatcher());
        $view
            ->setDatasource($datasource)
            ->setViewDefinition($viewDefinition)
            ->setInputDefinition($inputDefinition)
            //->enableVisualFilter('mod3')
        ;

        $renderer = $view->createView($request);
        $output = $renderer->render();
    }

    /**
     * Basic testing for FormTwigView coverage, later will be more advanced tests
     */
    public function testFormTwigView()
    {
        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $datasource = new IntArrayDatasource();
        $inputDefinition = new InputDefinition($datasource, ['limit_default' => 7]);

        $viewDefinition = new ViewDefinition([
            'default_display' => 'page',
            'enabled_filters' => ['odd_or_even'],
            'templates' => [
                'page' => 'module:udashboard:views/Page/page.html.twig',
            ],
        ]);

        $view = new FormTwigView($this->createTwigEnv(), new EventDispatcher(), $this->createFormFactory());
        $view
            ->setDatasource($datasource)
            ->setViewDefinition($viewDefinition)
            ->setInputDefinition($inputDefinition)
            //->enableVisualFilter('mod3')
            ->handleRequest($request)
        ;

        $renderer = $view->createView($request);
        $output = $renderer->render();
    }
}
