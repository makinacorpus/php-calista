<?php

namespace MakinaCorpus\Calista\Action;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait AuthorizationActionProviderTrait
{
    private $authorizationChecker;
    private $container;

    /**
     * {@inheritdoc}
     */
    final public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Set authorization checker
     *
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    final public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Get the container
     *
     * @return ContainerInterface
     */
    final protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Is current user granted.
     *
     * @param mixed $attributes
     * @param mixed $object
     *
     * @return bool
     */
    final protected function isGranted($attributes, $object = null)
    {
        return $this->authorizationChecker->isGranted($attributes, $object);
    }
}
