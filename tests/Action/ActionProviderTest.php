<?php

namespace MakinaCorpus\Dashboard\Tests\Drupal;

use MakinaCorpus\Dashboard\Action\Action;
use MakinaCorpus\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;

class ActionProviderTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Tests a lot of stuff
     */
    public function testActionRegistry()
    {
        $registry = new ActionRegistry();

        $providerSupporting = $this
            ->getMockBuilder(ActionProviderInterface::class)
            ->getMock()
        ;
        $providerSupporting
            ->method('supports')
            ->willReturn(true)
        ;
        $providerSupporting
            ->expects($this->once())
            ->method('getActions')
            ->willReturn([new Action("Foo", 'foo'), new Action("Bar", 'bar')])
        ;

        $providerNotSupporting = $this
            ->getMockBuilder(ActionProviderInterface::class)
            ->getMock()
        ;
        $providerNotSupporting
            ->method('supports')
            ->willReturn(false)
        ;
        $providerNotSupporting
            ->expects($this->never())
            ->method('getActions')
            ->willReturn([new Action("Baz", 'baz')])
        ;

        $registry->register($providerSupporting);
        $registry->register($providerNotSupporting);

        $actions = $registry->getActions((object)[]);
        $this->assertCount(2, $actions);
    }

    public function testActionRegistryInContainer()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        $registry = new ActionRegistry();
        // @todo test actions registration
    }
}
