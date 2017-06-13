<?php

namespace MakinaCorpus\Dashboard\Tests\Drupal;

use MakinaCorpus\Dashboard\Drupal\Action\Action;
use MakinaCorpus\Dashboard\Drupal\Action\ActionProviderInterface;
use MakinaCorpus\Dashboard\Drupal\Action\ActionRegistry;

class ActionProviderTest extends \PHPUnit_Framework_TestCase
{
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
}
