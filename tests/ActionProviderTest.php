<?php

namespace MakinaCorpus\Drupal\Dashboard\Tests;

use MakinaCorpus\Drupal\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Drupal\Dashboard\Action\Action;

class ActionProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests a lot of stuff
     */
    public function testActionRegistry()
    {
        $registry = new ActionRegistry();

        $providerSupporting = $this
            ->getMockBuilder('\MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface')
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
            ->getMockBuilder('\MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface')
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
