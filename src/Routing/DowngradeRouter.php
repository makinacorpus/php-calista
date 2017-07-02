<?php

namespace MakinaCorpus\Calista\Routing;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Avoids crashes in degraded environment, but this should never happen
 * in a real life application, we need a router.
 */
class DowngradeRouter implements UrlGeneratorInterface
{
    private $context;

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        // @todo use context if availabe
        if ($parameters) {
            return $name . '?' . http_build_query($parameters);
        }

        return $name;
    }
}
