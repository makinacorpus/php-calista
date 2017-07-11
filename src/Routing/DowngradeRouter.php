<?php

namespace MakinaCorpus\Calista\Routing;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Avoids crashes in degraded environment, but this should never happen
 * in a real life application, we need a router.
 */
class DowngradeRouter implements RouterInterface
{
    private $context;

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        // @todo use context if availabe
        if ($parameters) {
            return $name . '?' . http_build_query($parameters);
        }

        return $name;
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function getRouteCollection()
    {
        return new RouteCollection();
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore
     */
    public function match($pathinfo)
    {
        throw new ResourceNotFoundException();
    }
}
