<?php

namespace MakinaCorpus\Calista\Routing;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * Handles the 'destination' GET parameter transparently and proceed with
 *
 * Choice of default parameter name is to make it Drupal-compatible.
 */
class RedirectRouter implements EventSubscriberInterface, RouterInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['onKernelResponse', -4096]
            ],
        ];
    }

    /**
     * @var string
     */
    private $parameterName = '';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Default constructor
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router, RequestStack $requestStack, $parameterName = 'destination')
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->parameterName = $parameterName;
    }

    /**
     * On kernel response, redirect if possible
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();

        if ($response instanceof RedirectResponse) {
            $request = $this->requestStack->getMasterRequest();

            // Alter the given response to redirect the destination in case
            // we have one in the request
            $destination = $request->query->get($this->parameterName);
            if ($destination) {

                // For security reasons, disallow redirect when they do not
                // point internally on this site
                $reference = $request->getBaseUrl();
                if ($reference !== substr($destination, 0, strlen($reference))) {
                    return;
                }

                // @todo
                // In order to avoid infinite redirect loops, we should also
                // that our redirected URL does not have a 'destination' GET
                // parameter itself.

                // Seems we are OK, and I guess that the given destination
                // should be too, so let's just redirect using it
                $event->setResponse(new RedirectResponse($destination), $response->getStatusCode());
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function match($pathinfo)
    {
        return $this->router->match($pathinfo);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function setContext(RequestContext $context)
    {
        return $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        if (isset($parameters['_destination'])) {

            // Keep a copy of original destination
            $destination = $parameters['_destination'];
            unset($parameters['_destination']);

            // We have a conflict with another parameter, do not allow
            // a specific controller to crash because of us.
            if (isset($parameters[$this->parameterName])) {
                return $this->router->generate($name, $parameters, $referenceType);
            }

            $request = $this->requestStack->getMasterRequest();

            // If the current master query has a destination, do not alter
            // it and re-use it instead. Consider it as being safe already.
            if ($request->query->has($this->parameterName)) {
                $destination = $request->query->get($this->parameterName);
            } else {
                $route = null;
                $routeParameters = [];

                // Build destination using the internal router, first use the
                // given string as a route if provided
                if (is_string($destination)) {
                    $route = $destination;
                } else {
                    $route = $request->attributes->get('_route');
                    $routeParameters = $request->attributes->get('_route_params');
                }

                // If the user asked for an external URL, provide destination
                // as an absolute URL too, case in which another site might
                // consume it.
                $destination = $this->router->generate($route, $routeParameters, $referenceType);
            }

            $parameters[$this->parameterName] = $destination;
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }
}
