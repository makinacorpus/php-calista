<?php

namespace MakinaCorpus\Dashboard\Tests\Page;

use MakinaCorpus\Dashboard\Datasource\Configuration;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\FormPageBuilder;
use MakinaCorpus\Dashboard\Page\PageBuilder;
use MakinaCorpus\Dashboard\Page\PageResult;
use MakinaCorpus\Dashboard\Page\SortCollection;
use MakinaCorpus\Dashboard\Tests\Mock\IntArrayDatasource;
use MakinaCorpus\Dashboard\Twig\PageExtension;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the page builder
 */
class PageBuilderTest extends \PHPUnit_Framework_TestCase
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
     * Tests basics
     */
    public function testBasics()
    {
        $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $configuration = new Configuration(['limit_default' => 7]);

        $pageBuilder = new PageBuilder($this->createTwigEnv(), new EventDispatcher());
        $pageBuilder
            ->setDatasource(new IntArrayDatasource())
            ->setAllowedTemplates([
                'page' => 'page.html.twig',
            ])
            ->setDefaultDisplay('page')
            ->setConfiguration($configuration)
            ->enableFilter('odd_or_even')
            ->enableVisualFilter('mod3')
        ;

        $result = $pageBuilder->search($request);
        $this->assertInstanceOf(PageResult::class, $result);
        $this->assertSame($configuration, $pageBuilder->getConfiguration());

        // Ensure filters etc
        $filters = $result->getFilters();
        $this->assertSame('odd_or_even', reset($filters)->getField());
        $this->assertSame('Odd or Even', reset($filters)->getTitle());
        $visualFilters = $result->getVisualFilters();
        $this->assertSame('mod3', reset($visualFilters)->getField());
        $this->assertSame('Modulo 3', reset($visualFilters)->getTitle());

        $items = $result->getItems();
        $query = $result->getQuery();

        $this->assertCount(7, $items);
        $this->assertSame(3, $query->getPageNumber());
        $this->assertSame(128, $items->getTotalCount());

        // Ensure sorting was OK
        $itemsArray = iterator_to_array($items);
        $this->assertGreaterThan($itemsArray[1], $itemsArray[0]);

        // Is sort collection OK?
        $this->assertInstanceOf(SortCollection::class, $result->getSortCollection());

        // Build a page, for fun
        $pageView = $pageBuilder->createPageView($result);
        $rendered = $pageView->render();
    }

    /**
     * Basic testing for FormPageBuilder coverage, later will be more advanced tests
     */
    public function testFormPageBuilder()
    {
                $request = new Request([
            'odd_or_even' => 'odd',
            'page' => 3,
            'st' => 'value',
            'by' => Query::SORT_DESC,
        ], [], ['_route' => '_test_route']);

        $configuration = new Configuration(['limit_default' => 7]);

        $pageBuilder = new FormPageBuilder($this->createTwigEnv(), new EventDispatcher(), $this->createFormFactory());
        $pageBuilder
            ->setDatasource(new IntArrayDatasource())
            ->setAllowedTemplates([
                'page' => 'page.html.twig',
            ])
            ->setDefaultDisplay('page')
            ->setConfiguration($configuration)
            ->enableFilter('odd_or_even')
            ->enableVisualFilter('mod3')
            ->handleRequest($request)
        ;

        $result = $pageBuilder->search($request);

        // Build a page, for fun
        $pageView = $pageBuilder->createPageView($result);
        $rendered = $pageView->render();
    }
}
