<?php

namespace Drupal\Module\udashboard;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use MakinaCorpus\Dashboard\DependencyInjection\Compiler\PageDefinitionRegisterPass;
use MakinaCorpus\Dashboard\Drupal\DependencyInjection\Compiler\ActionProviderRegisterPass;
use MakinaCorpus\Dashboard\Drupal\DependencyInjection\Compiler\PortletRegisterPass;
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
        $container->addCompilerPass(new ActionProviderRegisterPass());
        $container->addCompilerPass(new PageDefinitionRegisterPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new PortletRegisterPass());
    }
}
