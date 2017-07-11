<?php

namespace MakinaCorpus\Calista\Tests\Routing;

use MakinaCorpus\Calista\Routing\DowngradeRouter;
use MakinaCorpus\Calista\Routing\RedirectRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;

class RedirectRouterTest extends \PHPUnit_Framework_TestCase
{
    public function testNoSideEffectWithoutRedirect()
    {
        $requestStack = new RequestStack();
        $referenceRouter = new DowngradeRouter();
        $redirectRouter = new RedirectRouter($referenceRouter, $requestStack, 'redirect_to');

        // Ensures that reference router does return something value
        $this->assertSame(
            'foo?a=1&b=2',
            $referenceRouter->generate('foo', ['a' => 1, 'b' => 2])
        );

        // Ensure that when no asked for a redirect, route is untouched
        $this->assertSame(
            $referenceRouter->generate('some_route', ['a' => 1, 'destination' => 'somewhere']),
            $redirectRouter->generate('some_route', ['a' => 1, 'destination' => 'somewhere'])
        );

        // Same with a custom destination parameter set
        $this->assertSame(
            $referenceRouter->generate('some_other_route', ['b' => 2, 'redirect_to' => 'somewhere']),
            $redirectRouter->generate('some_other_route', ['b' => 2, 'redirect_to' => 'somewhere'])
        );
    }

    public function testAlreadyExistingRedirectIsNotDropped()
    {
        $requestStack = new RequestStack();
        $referenceRouter = new DowngradeRouter();
        $redirectRouter = new RedirectRouter($referenceRouter, $requestStack, 'redirect_to');

        // Create some request, the current destination is always being looked
        // up into the GET parameters instead of the route attributes, since the
        // route might not have requested it
        $currentRequest = new Request(['redirect_to' => 'some_previsouly_set_location'], [], [
            '_route' => 'the_current_route',
            '_route_params' => ['foo' => 'bar', 'a' => 1],
        ]);
        $requestStack->push($currentRequest);

        // Same with a custom destination parameter set
        $this->assertSame(
            'some_other_route?b=2&redirect_to=' . rawurlencode('some_previsouly_set_location'),
            $redirectRouter->generate('some_other_route', ['b' => 2, '_destination' => 1])
        );
    }

    public function testBasicUrlGeneration()
    {
        $requestStack = new RequestStack();
        $referenceRouter = new DowngradeRouter();
        $redirectRouter = new RedirectRouter($referenceRouter, $requestStack, 'redirect_to');

        // Redirect router uses Symfony's extra attributes: we assume that the
        // route parameters are those that Symfony's real router matched, so we
        // internally use the _route_params attribute instead of the real query
        $currentRequest = new Request([], [], [
            '_route' => 'the_current_route',
            '_route_params' => ['foo' => 'bar', 'a' => 1],
        ]);
        $requestStack->push($currentRequest);

        // Let's add a sub request, from Symfony's perspective, only the master
        // request is important for the browser to redirect, so let's ensure
        // that sub-requests don't mess up with generation
        $subRequest = new Request([], [], [
            '_route' => 'the_sub_route',
            '_route_params' => ['foo' => 'baz', 'a' => 2],
        ]);
        $requestStack->push($subRequest);

        // User asked for a redirect on the current route
        $this->assertSame(
            'some_other_route?b=2&redirect_to=' . rawurlencode('the_current_route?foo=bar&a=1'),
            $redirectRouter->generate('some_other_route', ['b' => 2, '_destination' => 1])
        );

        // User asked for a custom (already generated) destination
        $this->assertSame(
            'some_other_route?b=2&redirect_to=' . rawurlencode('a_custom_destination'),
            $redirectRouter->generate('some_other_route', ['b' => 2, '_destination' => 'a_custom_destination'])
        );

        // Also, we should not mess up absolute URLs
        $this->assertSame(
            'some_other_route?b=2&redirect_to=' . rawurlencode('http://perdu.com?test=1'),
            $redirectRouter->generate('some_other_route', ['b' => 2, '_destination' => 'http://perdu.com?test=1'])
        );

        // Do not overwrite parameter if name conflict with another
        $this->assertSame(
            'some_other_route?b=2&redirect_to=i_am_not_modified',
            $redirectRouter->generate('some_other_route', ['b' => 2, '_destination' => 'http://perdu.com?test=1', 'redirect_to' => 'i_am_not_modified'])
        );
    }

    public function testResponseAlteration()
    {
        $requestStack = new RequestStack();
        $referenceRouter = new DowngradeRouter();
        $redirectRouter = new RedirectRouter($referenceRouter, $requestStack, 'redirect_to');
        $kernel = new HttpKernel(new EventDispatcher(), new ControllerResolver());

        // Basic query
        $currentRequest = new Request([], [], [
            '_route' => 'the_current_route',
            '_route_params' => ['foo' => 'bar', 'a' => 1],
        ]);
        $requestStack->push($currentRequest);

        $response = new RedirectResponse('http://perdu.com');
        $event = new FilterResponseEvent($kernel, $currentRequest, HttpKernelInterface::MASTER_REQUEST, $response);
        $redirectRouter->onKernelResponse($event);
        $this->assertSame($response, $event->getResponse());

        // Query with a redirect
        $requestStack->pop();
        $currentRequest = new Request(['redirect_to' => 'i_am_going_nowhere_else'], [], [
            '_route' => 'the_current_route',
            '_route_params' => ['foo' => 'bar', 'a' => 1],
        ]);
        $requestStack->push($currentRequest);
        $event = new FilterResponseEvent($kernel, $currentRequest, HttpKernelInterface::MASTER_REQUEST, $response);
        $redirectRouter->onKernelResponse($event);
        $altereredResponse = $event->getResponse();
        $this->assertNotSame($response, $altereredResponse);
        $this->assertContains('i_am_going_nowhere_else', $altereredResponse->getContent());
    }
}
