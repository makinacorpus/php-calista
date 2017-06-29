<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Action\ActionRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

/**
 * Basics for tests
 */
trait ContainerAwareTestTrait
{
    private function createPropertyAccessor()
    {
        return Kernel::createPropertyAccessor();
    }

    private function createPropertyInfoExtractor()
    {
        return Kernel::createPropertyInfoExtractor();
    }

    private function createTwigEnv(ActionRegistry $actionRegistry = null)
    {
        return Kernel::createTwigEnv($actionRegistry);
    }

    /**
     * Create a form factory with the bare minimum we need
     *
     * @return FormFactoryInterface
     */
    private function createFormFactory()
    {
        return  Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory()
        ;
    }

    /**
     * Create a container with page definitions
     *
     * @param string $configFilename
     *
     * @return ContainerBuilder
     *   Container is not compiled yet, so you can furnish more services
     */
    private function getContainer($configFilename = null)
    {
        $kernel = new Kernel(uniqid('test'), true);

        if ($configFilename) {
            $kernel->setConfigurationFile($configFilename);
        }

        $kernel->boot();

        return $kernel->getContainer();
    }
}
