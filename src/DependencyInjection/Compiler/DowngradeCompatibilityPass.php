<?php

namespace MakinaCorpus\Calista\DependencyInjection\Compiler;

use MakinaCorpus\Calista\Routing\DowngradeRouter;
use MakinaCorpus\Calista\Twig\DowngradeCompatibilityExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Registers some foo components for downgraded mode where there is no complete
 * Symfony FrameworkBundle in place
 *
 * @codeCoverageIgnore
 */
class DowngradeCompatibilityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // 'form_widget' even if unused exists in our templates, and causes
        // trouble for compiling templates: it will crash. We are going to
        // register a dummy implementation.
        if (!$container->has('form.extension')) {
            $container->addDefinitions([
                'calista.twig.downgrade_compatibility' => (new Definition())
                    ->setClass(DowngradeCompatibilityExtension::class)
                    ->addTag('twig.extension')
            ]);
        }

        // Add a foo router for twig actions extension
        if (!$container->has('router')) {
            $container->addDefinitions([
                'router' => (new Definition())
                    ->setClass(DowngradeRouter::class)
            ]);
        }

        // And request stack, this will be useful mostly for tests
        if (!$container->has('request_stack')) {
            $container->addDefinitions([
                'request_stack' => (new Definition())
                    ->setClass(RequestStack::class)
            ]);
        }
    }
}
