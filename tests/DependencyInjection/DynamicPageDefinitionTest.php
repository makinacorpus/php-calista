<?php

namespace MakinaCorpus\Dashboard\Tests\View;

use MakinaCorpus\Dashboard\Error\ConfigurationError;
use MakinaCorpus\Dashboard\Tests\Mock\BrokenDynamicPageDefinitionOne;
use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Dashboard\Tests\Mock\DynamicPageDefinitionClass;
use MakinaCorpus\Dashboard\Tests\Mock\DynamicPageDefinitionName;
use MakinaCorpus\Dashboard\Tests\Mock\DynamicPageDefinitionService;
use MakinaCorpus\Dashboard\Tests\Mock\IntArrayDatasource;

/**
 * Tests the views
 */
class DynamicPageDefinitionTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Test basic behaviour
     */
    public function testOne()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        foreach ([
            DynamicPageDefinitionService::class,
            DynamicPageDefinitionClass::class,
            DynamicPageDefinitionName::class
        ] as $pageClass) {

            /** @var \MakinaCorpus\Dashboard\DependencyInjection\PageDefinitionInterface $page */
            $page = new $pageClass();
            $page->setContainer($container);

            // This will only cover but do not test anything
            $page->getInputDefinition();

            // And this do test
            $viewDefinition = $page->getViewDefinition();

            // Order is kept, properties are only those defined in the page
            $this->assertSame(['id', 'type', 'thousands'], $viewDefinition->getDisplayedProperties());
            $this->assertSame(['default' => 'module:udashboard:views/Page/page.html.twig'], $viewDefinition->getTemplates());

            // Callback is set
            $options = $viewDefinition->getPropertyDisplayOptions('thousands');
            $this->assertSame([$page, 'renderThousands'], $options['callback']);

            // Type is kept
            $options = $viewDefinition->getPropertyDisplayOptions('id');
            $this->assertSame('integer', $options['type']);

            $this->assertInstanceOf(IntArrayDatasource::class, $page->getDatasource());
        }
    }

    /**
     * Test with datasource
     */
    public function testDatasourceBehavior()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        $page = new BrokenDynamicPageDefinitionOne();

        // Existing datasource service id, no container, exception
        try {
            $page->setDatasourceId('_test_datasource');
            $page->getDatasource();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        $page->setContainer($container);

        // Non existing datasource service id, no container, exception
        try {
            $page->setDatasourceId('non_existing_test_datasource');
            $page->getDatasource();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // Existing datasource service id, container
        $page->setDatasourceId('_test_datasource');
        // Change it without changing the name, no exception
        $page->setDatasourceId('_test_datasource');
        // Fetch it, no exceptions
        $page->getDatasource();

        // Changing it now that it has been instanciated, exception
        try {
            $page->setDatasourceId('_test_datasource');
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // Change it, change the name, exception
        try {
            $page->setDatasourceId('_test_datasource');
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // New one and fetch with no service name set exception
        $page = new BrokenDynamicPageDefinitionOne();
        $page->setContainer($container);
        try {
            $page->getDatasource();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // No container, invalid class name
        $page->setDatasourceId("\\Non\\Existing\\Testing\\Class\\And\\Explode");
        try {
            $page->getDatasource();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // New one and fetch with no service name set exception
        $page = new BrokenDynamicPageDefinitionOne();
        $page->setContainer($container);
        // Set a datasource instance should be ok
        $page->setDatasource(new IntArrayDatasource());
        // Set it twice should fail
        try {
            $page->setDatasource(new IntArrayDatasource());
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertTrue(true);
        }

        // This definition is definitely broken, so it should explode
        try {
            $page->getViewDefinition();
            $this->fail();
        } catch (ConfigurationError $e) {
            $this->assertContains("cannot have more than 3 required parameters", $e->getMessage());
        }
    }
}
