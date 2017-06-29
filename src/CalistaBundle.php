<?php

namespace MakinaCorpus\Calista;

use MakinaCorpus\Calista\DependencyInjection\CalistaExtension;
use MakinaCorpus\Calista\DependencyInjection\Compiler\ActionProviderRegisterPass;
use MakinaCorpus\Calista\DependencyInjection\Compiler\DowngradeCompatibilityPass;
use MakinaCorpus\Calista\DependencyInjection\Compiler\PageDefinitionRegisterPass;
use MakinaCorpus\Calista\DependencyInjection\Compiler\RegisterTemplatePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Testing kernel
 */
class CalistaBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ActionProviderRegisterPass());
        $container->addCompilerPass(new DowngradeCompatibilityPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 50 /* Make it run before twig's one */);
        $container->addCompilerPass(new PageDefinitionRegisterPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new RegisterTemplatePass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        // I seriously do not believe in autodiscovery, and I wanted the class
        // names to be consistent. This is explicit: get over it.
        return new CalistaExtension();
    }
}
