<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\DependencyInjection\Compiler\PageDefinitionRegisterPass;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Testing kernel
 */
class Kernel extends BaseKernel
{
    private $configurationFilename;
    private $extensions = [];
    private $mockBundles = [];

    /**
     * Set configuration file
     *
     * @param string $filename
     */
    public function setConfigurationFile($filename)
    {
        $this->configurationFilename = $filename;
    }

    /**
     * Add extension
     *
     * @param Extension $extension
     */
    public function addExtension(Extension $extension)
    {
        $this->mockBundles[] = new KernelBundle($extension);
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        if ($this->configurationFilename) {
            $loader->load(__DIR__.'/kernel.services.yml');
            $loader->load($this->configurationFilename);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareContainer(ContainerBuilder $container)
    {
        parent::prepareContainer($container);

        $container->addCompilerPass(new PageDefinitionRegisterPass());
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return $this->mockBundles;
    }
}
