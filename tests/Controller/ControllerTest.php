<?php

namespace MakinaCorpus\Calista\Tests\Controller;

use MakinaCorpus\Calista\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Calista\Tests\Mock\FooAjaxController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests the AJAX controller
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Test basics for the ajax controller
     */
    public function testAjaxController()
    {
        $container = $this->getContainer();

        $controller = new FooAjaxController();
        $controller->setContainer($container);

        try {
            $controller->refreshAction(new Request(['_page_id' => "Sure, maybe I'll be there?"]));
            $this->fail();
        } catch (NotFoundHttpException $e) {
            $this->assertTrue(true);
        }

        // Refresh action
        $response = $controller->refreshAction(new Request(['_page_id' => 'int_array_page']));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $responseArray = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('query', $responseArray);
        $this->assertArrayHasKey('blocks', $responseArray);
        $this->assertArrayHasKey('filters', $responseArray['blocks']);
        $this->assertArrayHasKey('display_mode', $responseArray['blocks']);
        $this->assertArrayHasKey('sort_links', $responseArray['blocks']);
        $this->assertArrayHasKey('item_list', $responseArray['blocks']);
        $this->assertArrayHasKey('pager', $responseArray['blocks']);

        // Search action
        $response = $controller->searchAction(new Request(['_page_id' => 'int_array_page']));
        $this->assertInstanceOf(JsonResponse::class, $response);
        $responseArray = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('query', $responseArray);
        $this->assertArrayHasKey('blocks', $responseArray);
        $this->assertArrayHasKey('filters', $responseArray['blocks']);
        $this->assertArrayHasKey('display_mode', $responseArray['blocks']);
        $this->assertArrayHasKey('sort_links', $responseArray['blocks']);
        $this->assertArrayHasKey('item_list', $responseArray['blocks']);
        $this->assertArrayHasKey('pager', $responseArray['blocks']);
    }
}
