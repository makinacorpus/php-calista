<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Action\ActionRegistry;
use MakinaCorpus\Calista\DependencyInjection\Compiler\ActionProviderRegisterPass;
use MakinaCorpus\Calista\DependencyInjection\Compiler\PageDefinitionRegisterPass;
use MakinaCorpus\Calista\DependencyInjection\ViewFactory;
use MakinaCorpus\Calista\Twig\ActionExtension;
use MakinaCorpus\Calista\Twig\PageExtension;
use MakinaCorpus\Calista\View\Html\TwigView;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Basics for tests
 */
trait ContainerAwareTestTrait
{
    private function createPropertyAccessor()
    {
        return new PropertyAccessor();
    }

    private function createPropertyInfoExtractor()
    {
        $listExtractors = [
            new IntProperyIntoExtractor(),
            new ReflectionExtractor(),
        ];

        $typeExtractors = [
            new IntProperyIntoExtractor(),
            new ReflectionExtractor(),
            new PhpDocExtractor(),
        ];

        $descriptionExtractors = [
            new IntProperyIntoExtractor(),
            new PhpDocExtractor(),
        ];

        $accessExtractors = [
            new IntProperyIntoExtractor(),
            new ReflectionExtractor(),
        ];

        return new PropertyInfoExtractor($listExtractors, $typeExtractors, $descriptionExtractors, $accessExtractors);
    }

    /**
     * Create a twig environment with the bare minimum we need
     *
     * This is public because of the container factory
     *
     * @param ActionRegistry $actionRegistry
     *
     * @return \Twig_Environment
     */
    public function createTwigEnv(ActionRegistry $actionRegistry = null)
    {
        $twigEnv = new \Twig_Environment(
            new \Twig_Loader_Array([
                'module:calista:views/Action/action-single.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/views/Action/action-single.html.twig'),
                'module:calista:views/Action/actions.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/views/Action/actions.html.twig'),
                'module:calista:views/Page/page-dynamic-table.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/views/Page/page-dynamic-table.html.twig'),
                'module:calista:views/Page/page-grid.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/views/Page/page-grid.html.twig'),
                'module:calista:views/Page/page.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/views/Page/page.html.twig'),
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
            return $route . '&' . http_build_query($routeParameters);
        }), ['is_safe' => ['html']]);
        $twigEnv->addFunction(new \Twig_SimpleFunction('form_widget', function () {
            return 'FORM_WIDGET';
        }));
        $twigEnv->addFunction(new \Twig_SimpleFunction('form_errors', function () {
            return 'FORM_ERRORS';
        }));
        $twigEnv->addFunction(new \Twig_SimpleFunction('form_rest', function () {
            return 'FORM_REST';
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

        $twigEnv->addExtension(new PageExtension(new RequestStack(), $this->createPropertyAccessor(), $this->createPropertyInfoExtractor(), true));
        if ($actionRegistry) {
            $twigEnv->addExtension(new ActionExtension($actionRegistry));
        } else {
            $twigEnv->addFunction(new \Twig_SimpleFunction('calista_actions', function () {
                return 'ACTIONS';
            }));
        }

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
     *
     * @return ContainerBuilder
     *   Container is not compiled yet, so you can furnish more services
     */
    private function createContainerWithPageDefinitions()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $propertyInfoExtractor = $this->createPropertyInfoExtractor();
        $container->set('property_info', $propertyInfoExtractor);

        $container->addDefinitions([
            'event_dispatcher' => (new Definition())
                ->setClass(EventDispatcher::class)
                ->setPublic(true)
        ]);

        // Action
        $container->addDefinitions([
            'calista.action_provider_registry' => (new Definition())
                ->setClass(ActionRegistry::class)
                ->setPublic(true)
        ]);
        $container->addDefinitions([
            'calista.action_provider_int' => (new Definition())
                ->setClass(IntActionProvider::class)
                ->addTag('calista.action_provider')
                ->setPublic(true)
        ]);
        $container->addCompilerPass(new ActionProviderRegisterPass());

        // Twig
        $container->addDefinitions([
            'twig' => (new Definition())
                ->setClass(\Twig_Environment::class)
                ->setPublic(true)
                ->addArgument(new Reference('calista.action_provider_registry'))
                ->setFactory([$this, 'createTwigEnv'])
        ]);

        // Views and pages factory
        $container->addDefinitions([
            'calista.view_factory' => (new Definition())
                ->setClass(ViewFactory::class)
                ->setArguments([
                    new Reference('service_container'),
                ])
                ->setPublic(true)
        ]);
        $container->addCompilerPass(new PageDefinitionRegisterPass());

        // Views
        $container->addDefinitions([
            'calista.view.twig_page' => (new Definition())
                ->setClass(TwigView::class)
                ->setArguments([new Reference('twig'), new Reference('event_dispatcher')])
                ->setPublic(true)
                ->addTag('calista.view', ['id' => 'twig_page'])
        ]);

        // Pages
        $container->addDefinitions([
            '_test_view' => (new Definition())
                ->setClass(FooPageDefinition::class)
                ->setPublic(true)
                ->addTag('calista.page_definition', ['id' => 'int_array_page'])
        ]);
        $container->addDefinitions([
            '_test_datasource' => (new Definition())
                ->setClass(IntArrayDatasource::class)
                ->setPublic(true)
                ->addTag('calista.datasource', ['id' => 'int_array_datasource'])
        ]);

        return $container;
    }
}
