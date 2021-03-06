<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Action\ActionRegistry;
use MakinaCorpus\Calista\CalistaBundle;
use MakinaCorpus\Calista\Routing\DowngradeRouter;
use MakinaCorpus\Calista\Twig\ActionExtension;
use MakinaCorpus\Calista\Twig\PageExtension;
use MakinaCorpus\Calista\View\PropertyRenderer;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use MakinaCorpus\Calista\Controller\PageRenderer;

/**
 * Testing kernel
 */
class Kernel extends BaseKernel
{
    /**
     * Container factory
     *
     * @return \Symfony\Component\PropertyAccess\PropertyAccessor
     */
    static public function createPropertyAccessor()
    {
        return new PropertyAccessor();
    }

    /**
     * Container factory
     *
     * @return \Symfony\Component\PropertyInfo\PropertyInfoExtractor
     */
    static public function createPropertyInfoExtractor()
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
    static public function createTwigEnv(ActionRegistry $actionRegistry = null)
    {
        $twigEnv = new \Twig_Environment(
            new \Twig_Loader_Array([
                '@calista/action/action-single.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/templates/action/action-single.html.twig'),
                '@calista/action/actions.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/templates/action/actions.html.twig'),
                '@calista/page/drupal/page-node-grid.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/templates/page/drupal/page-node-grid.html.twig'),
                '@calista/page/drupal/page-node-table.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/templates/page/drupal/page-node-table.html.twig'),
                '@calista/page/page-navbar.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/templates/page/page-navbar.html.twig'),
                '@calista/page/page.html.twig' => file_get_contents(dirname(dirname(__DIR__)) . '/templates/page/page.html.twig'),
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

        $propertyRenderer = new PropertyRenderer(
            self::createPropertyAccessor(),
            self::createPropertyInfoExtractor()
        );

        $twigEnv->addExtension(new PageExtension(new RequestStack(), $propertyRenderer));
        if ($actionRegistry) {
            $twigEnv->addExtension(new ActionExtension($actionRegistry, new RequestStack(), new DowngradeRouter()));
        } else {
            $twigEnv->addFunction(new \Twig_SimpleFunction('calista_actions', function () {
                return 'ACTIONS';
            }));
        }

        return $twigEnv;
    }

    private $configurationFilename;

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
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/kernel.services.yml');

        if ($this->configurationFilename) {
            $loader->load($this->configurationFilename);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [new CalistaBundle()];
    }
}
