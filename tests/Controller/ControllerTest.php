<?php

namespace MakinaCorpus\Calista\Tests\Controller;

use MakinaCorpus\Calista\Tests\Mock\ContainerAwareTestTrait;
use MakinaCorpus\Calista\Tests\Mock\FooAjaxController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

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

        $session = new Session(new MockArraySessionStorage());
        $request = new Request(['_page_id' => 'int_array_page']);
        $request->setSession($session);

        // With 'int_array_page' it cannot work, session will contain the
        // real internal identifier, which is '_test_view' from our container
        // definition
        try {
            $controller->refreshAction($request);
            $this->fail();
        } catch (NotFoundHttpException $e) {
            $this->assertTrue(true);
        }

        // It should not work because there is no CSRF token in session
        try {
            $controller->refreshAction($request);
            $this->fail();
        } catch (NotFoundHttpException $e) {
            $this->assertTrue(true);
        }

        // Render the page at least once, and prey for session to have
        // our token, since we do not pass any input option, token will
        // be the page id
        $controller->renderPageForTest('int_array_page', $request);

        // And yes, page identifier has changed, since the internal one
        // is used instead
        $request = new Request(['_page_id' => '_test_view']);
        $request->setSession($session);

        // Refresh action
        $response = $controller->refreshAction($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $responseArray = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('query', $responseArray);
        $this->assertArrayHasKey('blocks', $responseArray);
        $this->assertArrayHasKey('filters', $responseArray['blocks']);
        $this->assertArrayHasKey('display_mode', $responseArray['blocks']);
        $this->assertArrayHasKey('sort_links', $responseArray['blocks']);
        $this->assertArrayHasKey('item_list', $responseArray['blocks']);
        $this->assertArrayHasKey('pager', $responseArray['blocks']);

        // And yes, page identifier has changed, since the internal one
        // is used instead
        $request = new Request(['_page_id' => '_test_view']);
        $request->setSession($session);

        // Search action
        $response = $controller->searchAction($request);
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
