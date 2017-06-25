<?php

namespace MakinaCorpus\Dashboard\Tests\View;

use MakinaCorpus\Dashboard\Datasource\InputDefinition;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Dashboard\Tests\Mock\FooPageDefinition;
use MakinaCorpus\Dashboard\Tests\Mock\IntArrayDatasource;
use MakinaCorpus\Dashboard\View\Html\FormTwigView;
use MakinaCorpus\Dashboard\View\Html\TwigView;
use MakinaCorpus\Dashboard\View\ViewDefinition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the views
 */
class TwigViewTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Tests the view factory, very basic tests
     */
    public function testViewFactoryGetPageDefinition()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        /** @var \MakinaCorpus\Dashboard\DependencyInjection\ViewFactory $factory */
        $factory = $container->get('udashboard.view_factory');
        $request = new Request();

        // Now ensures that we can find our definition
        $pageDefinition = $factory->getPageDefinition('_test_view', $request);
        $this->assertInstanceOf(FooPageDefinition::class, $pageDefinition);
        // @todo fix me
        //$this->assertSame('_test_view', $view->getId());
        $this->assertInstanceOf(IntArrayDatasource::class, $pageDefinition->getDatasource());
        $this->assertSame('_limit', $pageDefinition->getInputDefinition()->getLimitParameter());

        // And by identifier, and ensure the identifier is not the same as
        // the service identifier, but the one we added in the tag
        $pageDefinition = $factory->getPageDefinition('int_array_page', $request);
        $this->assertInstanceOf(FooPageDefinition::class, $pageDefinition);
        //$this->assertSame('int_array_page', $pageDefinition->getId());
        $this->assertInstanceOf(IntArrayDatasource::class, $pageDefinition->getDatasource());
        $this->assertSame('_limit', $pageDefinition->getInputDefinition()->getLimitParameter());

        // And by class
        $pageDefinition = $factory->getPageDefinition(FooPageDefinition::class, $request);
        //$this->assertSame(FooPageDefinition::class, $pageDefinition->getId());
        $this->assertInstanceOf(FooPageDefinition::class, $pageDefinition);
        $this->assertInstanceOf(IntArrayDatasource::class, $pageDefinition->getDatasource());
        $this->assertSame('_limit', $pageDefinition->getInputDefinition()->getLimitParameter());

        // Ensure we have some stuff that do not work
        try {
            $factory->getPageDefinition('_test_datasource', $request);
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

        // Ensure filters etc
        $filters = $inputDefinition->getFilters();
        $this->assertSame('odd_or_even', reset($filters)->getField());
        $this->assertSame('Odd or Even', reset($filters)->getTitle());
//         $visualFilters = $result->getVisualFilters();
//         $this->assertSame('mod3', reset($visualFilters)->getField());
//         $this->assertSame('Modulo 3', reset($visualFilters)->getTitle());

        $query = $inputDefinition->createQueryFromRequest($request);
        $items = $datasource->getItems($query);

        $this->assertCount(7, $items);
        $this->assertSame(3, $query->getPageNumber());
        $this->assertSame(128, $items->getTotalCount());

        // Ensure sorting was OK
        $itemsArray = iterator_to_array($items);
        $this->assertGreaterThan($itemsArray[1], $itemsArray[0]);

        // Build a page, for fun
        $response = $view->renderAsResponse($viewDefinition, $items, $query);
        $this->assertInstanceOf(Response::class, $response);
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
            'templates' => ['page' => 'module:udashboard:views/Page/page-dynamic-table.html.twig'],
        ]);

        $view = new TwigView($container->get('twig'), new EventDispatcher());
        $query = $inputDefinition->createQueryFromRequest($request);
        $items = $datasource->getItems($query);

        $output = $view->render($viewDefinition, $items, $query);
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
            'show_sort' => true,
            'templates' => [
                'page' => 'module:udashboard:views/Page/page.html.twig',
            ],
        ]);

        $query = $inputDefinition->createQueryFromRequest($request);
        $items = $datasource->getItems($query);

        $view = new FormTwigView($this->createTwigEnv(), new EventDispatcher(), $this->createFormFactory());
        $view->handleRequest($request, $items);

        $output = $view->render($viewDefinition, $items, $query);
    }
}
