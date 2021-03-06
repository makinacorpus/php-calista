<?php

namespace MakinaCorpus\Calista\Tests\DependencyInjection;

use MakinaCorpus\Calista\Tests\Mock\ContainerAwareTestTrait;

/**
 * Tests the views
 */
class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Test pretty much everything there's to test
     */
    public function testConfigurationAndRegistration()
    {
        $container = $this->getContainer(dirname(__DIR__).'/Mock/kernel.config.good.yml');

        $this->assertTrue($container->has('calista.view_factory'));
        $this->assertTrue($container->has('calista.config_page.page_one'));
        $this->assertTrue($container->has('calista.config_page.page_two'));

        /** @var \MakinaCorpus\Calista\DependencyInjection\ViewFactory $factory */
        $factory = $container->get('calista.view_factory');

        $pageOne1 = $factory->getPageDefinition('calista.config_page.page_one');
        $pageOne2 = $factory->getPageDefinition('the_first_page');

        foreach ([$pageOne1, $pageOne2] as $page) {
            $viewDefinition = $page->getViewDefinition();
            $this->assertSame('twig_page', $viewDefinition->getViewType());
            $this->assertSame(['mooh'], $viewDefinition->getEnabledFilters());
        }

        $pageTwo = $factory->getPageDefinition('calista.config_page.page_two');
        $inputDefinition = $pageTwo->getInputDefinition();
        $this->assertSame('do_search_this', $inputDefinition->getSearchParameter());
    }
}
