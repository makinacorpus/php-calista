<?php

namespace MakinaCorpus\Dashboard\Tests\Controller;

use MakinaCorpus\Dashboard\Drupal\Controller\AjaxPageController as AjaxPageController;
use MakinaCorpus\Dashboard\Tests\Mock\ContainerAwareTestTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests the page builder
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    use ContainerAwareTestTrait;

    /**
     * Test basics for the ajax controller
     */
    public function testDrupalAjaxController()
    {
        $container = $this->createContainerWithPageDefinitions();
        $container->compile();

        $controller = new AjaxPageController();
        $controller->setContainer($container);

        try {
            $controller->refreshAction(new Request(['name' => "Sure, maybe I'll be there?"]));
            $this->fail();
        } catch (NotFoundHttpException $e) {
            $this->assertTrue(true);
        }

        // Refresh action
        $response = $controller->refreshAction(new Request(['name' => 'int_array_page']));
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
        $response = $controller->searchAction(new Request(['name' => 'int_array_page']));
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
