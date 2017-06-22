<?php

namespace Drupal\Module\udashboard;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Dashboard\DependencyInjection\Compiler\ActionProviderRegisterPass;
use MakinaCorpus\Dashboard\DependencyInjection\Compiler\DowngradeCompatibilityPass;
use MakinaCorpus\Dashboard\DependencyInjection\Compiler\PageDefinitionRegisterPass;
use MakinaCorpus\Dashboard\Drupal\DependencyInjection\Compiler\ActionProcessorRegisterPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Implements Drupal 8 service provider.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DowngradeCompatibilityPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 50 /* Make it run before twig's one */);
        $container->addCompilerPass(new ActionProviderRegisterPass());
        $container->addCompilerPass(new ActionProcessorRegisterPass());
        $container->addCompilerPass(new PageDefinitionRegisterPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}
