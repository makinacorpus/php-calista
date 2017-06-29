<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Testing kernel
 */
class KernelBundle extends Bundle
{
    private $fooExtension;

    /**
     * Default constructor
     *
     * @param Extension $extension
     */
    public function __construct(Extension $extension)
    {
        $this->fooExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return $this->fooExtension;
    }
}
