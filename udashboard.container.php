<?php

namespace Drupal\Module\udashboard;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\Drupal\Dashboard\DependencyInjection\Compiler\ActionProviderRegisterPass;
use MakinaCorpus\Drupal\Dashboard\DependencyInjection\Compiler\PageBuilderRegisterPass;
use MakinaCorpus\Drupal\Dashboard\DependencyInjection\Compiler\PortletRegisterPass;

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
        $container->addCompilerPass(new PageBuilderRegisterPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new PortletRegisterPass());
    }
}
