<?php

namespace MakinaCorpus\Calista\DependencyInjection;

/**
 * Boilerplate code for service implementations.
 */
trait ServiceTrait /* implements ServiceInterface */
{
    private $id;

    /**
     * {@inheritdoc}
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }
}
