<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\DependencyInjection\Compiler\PageDefinitionRegisterPass;
use MakinaCorpus\Dashboard\Drupal\Action\ActionRegistry;
use MakinaCorpus\Dashboard\Page\PageBuilderFactory;
use MakinaCorpus\Dashboard\Twig\PageExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Basics for tests
 */
trait ContainerAwareTestTrait
{
    /**
     * Create a twig environment with the bare minimum we need
     *
     * @return \Twig_Environment
     */
    private function createTwigEnv()
    {
        $twigEnv = new \Twig_Environment(
            new \Twig_Loader_Filesystem([
                dirname(dirname(__DIR__)) . '/views/Page'
            ]),
            [
                'debug' => true,
                'strict_variables' => true,
                'autoescape' => 'html',
                'cache' => false,
                'auto_reload' => null,
                'optimizations' => -1,
            ]
        );

        $twigEnv->addFunction(new \Twig_SimpleFunction('path', function ($route, $routeParameters = []) {
            return $route . implode('&=', $routeParameters);
        }));
        $twigEnv->addFunction(new \Twig_SimpleFunction('form_widget', function () {
            return 'FORM_WIDGET';
        }));
        $twigEnv->addFunction(new \Twig_SimpleFunction('form_errors', function () {
            return 'FORM_ERRORS';
        }));
        $twigEnv->addFunction(new \Twig_SimpleFunction('form_rest', function () {
            return 'FORM_REST';
        }));
        $twigEnv->addFunction(new \Twig_SimpleFunction('udashboard_actions', function () {
            return 'ACTIONS';
        }));
        $twigEnv->addFilter(new \Twig_SimpleFilter('trans', function ($string, $params = []) {
            return strtr($string, $params);
        }));
        $twigEnv->addFilter(new \Twig_SimpleFilter('t', function ($string, $params = []) {
            return strtr($string, $params);
        }));
        $twigEnv->addFilter(new \Twig_SimpleFilter('time_diff', function ($value) {
            return (string)$value;
        }));
        $twigEnv->addExtension(new PageExtension(new RequestStack()));

        return $twigEnv;
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
     */
    private function createContainerWithPageDefinitions()
    {
        $container = new ContainerBuilder();
        $container->addDefinitions([
            'udashboard.page_builder_factory' => (new Definition())
                ->setClass(PageBuilderFactory::class)
                ->setArguments([new Reference('service_container'), $this->createFormFactory(), new ActionRegistry(), $this->createTwigEnv()])
                ->setPublic(true)
        ]);
        $container->addDefinitions([
            '_test_page_definition' => (new Definition())
                ->setClass(FooPageDefinition::class)
                ->setPublic(true)
                ->addTag('udashboard.page_definition', ['id' => 'int_array_page'])
        ]);
        $container->addDefinitions([
            '_test_datasource' => (new Definition())
                ->setClass(IntArrayDatasource::class)
                ->setPublic(true)
        ]);
        $container->addCompilerPass(new PageDefinitionRegisterPass());
        $container->compile();

        return $container;
    }
}
