<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Controller\AjaxController;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests a very broken dynamic page definition
 */
class FooAjaxController extends AjaxController
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Renders a page for testing
     */
    public function renderPageForTest($name, Request $request, array $inputOptions = [])
    {
        return $this->renderPage($name, $request, $inputOptions);
    }
}
