<?php

namespace MakinaCorpus\Calista\Tests\DependencyInjection;

use MakinaCorpus\Calista\DependencyInjection\ConfigPageDefinition;
use MakinaCorpus\Calista\DependencyInjection\PageDefinitionInterface;
use MakinaCorpus\Calista\Error\ConfigurationError;
use MakinaCorpus\Calista\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Calista\Tests\Mock\IntArrayDatasource;
use MakinaCorpus\Calista\View\Html\TwigView;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the views
 */
class ConfigPageDefinitionTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Run everything, prey for no errors
     *
     * @param ContainerInterface $container
     * @param PageDefinitionInterface $page
     */
    private function renderTheBouzin(ContainerInterface $container, PageDefinitionInterface $page)
    {
        $request = new Request();

        $inputDefinition = $page->getInputDefinition();
        $viewDefinition = $page->getViewDefinition();

        $query = $inputDefinition->createQueryFromRequest($request);
        $datasource = $page->getDatasource();
        $items = $datasource->getItems($query);

        /** @var \MakinaCorpus\Calista\DependencyInjection\ViewFactory $factory */
        $factory = $container->get('calista.view_factory');
        $view = $factory->getView($viewDefinition->getViewType());

        $view->render($viewDefinition, $items, $query);
    }

    /**
     * Test error behavior
     */
    public function testVariousErrors()
    {
        $config = [
            'view' => [
                'view_type' => 'foo'
            ],
        ];

        try {
            new ConfigPageDefinition($config);
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertContains('datasource', $e->getMessage());
        }

        $config = [
            'datasource' => 'test',
        ];

        try {
            new ConfigPageDefinition($config);
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertContains('view_type', $e->getMessage());
        }

        $config = [
            'view' => [
                'view_type' => 'foo'
            ],
            'datasource' => 'test',
        ];

        try {
            $page = new ConfigPageDefinition($config);
            $page->getDatasource();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertContains('container', $e->getMessage());
        }

        try {
            $page = new ConfigPageDefinition($config);
            $page->setContainer(new ContainerBuilder());
            $page->getDatasource();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertContains('datasource', $e->getMessage());
        }
    }

    /**
     * Test basic behaviour
     */
    public function testWithServiceIdentifiers()
    {
        $container = $this->getContainer();

        $config = [
            'datasource' => 'int_array_datasource',
            'view' => [
                'enabled_filters' => ['mooh'],
                'view_type' => 'twig_page'
            ],
            'input' => [
                'search_param' => 'astropolis',
            ],
        ];

        $page = new ConfigPageDefinition($config);
        $page->setContainer($container);

        $inputDefinition = $page->getInputDefinition();
        $this->assertSame('astropolis', $inputDefinition->getSearchParameter());

        $viewDefinition = $page->getViewDefinition();
        $this->assertSame(['mooh'], $viewDefinition->getEnabledFilters());

        $this->assertInstanceOf(IntArrayDatasource::class, $page->getDatasource());

        $this->renderTheBouzin($container, $page);
    }

    /**
     * Test with datasource
     */
    public function testWithClasses()
    {
        $container = $this->getContainer();

        $config = [
            'datasource' => IntArrayDatasource::class,
            'view' => [
                'view_type' => TwigView::class,
            ],
        ];

        $page = new ConfigPageDefinition($config);
        $page->setContainer($container);

        $this->assertInstanceOf(IntArrayDatasource::class, $page->getDatasource());

        $this->renderTheBouzin($container, $page);
    }
}
