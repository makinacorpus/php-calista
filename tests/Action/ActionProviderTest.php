<?php

namespace MakinaCorpus\Calista\Tests\Drupal;

use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Calista\Action\ActionProviderInterface;
use MakinaCorpus\Calista\Action\ActionRegistry;
use MakinaCorpus\Calista\Tests\Mock\ContainerAwareTestTrait;

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
        $container = $this->getContainer();

        $registry = new ActionRegistry();
        // @todo test actions registration
    }
}
