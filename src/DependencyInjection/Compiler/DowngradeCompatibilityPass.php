<?php

namespace MakinaCorpus\Dashboard\DependencyInjection\Compiler;

use MakinaCorpus\Dashboard\Twig\DowngradeCompatibilityExtension;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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
                'udashboard.twig.downgrade_compatibility' => (new Definition())
                    ->setClass(DowngradeCompatibilityExtension::class)
                    ->addTag('twig.extension')
            ]);
        }
    }
}
