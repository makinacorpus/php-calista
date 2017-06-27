<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Controller\AjaxControllerTrait;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Tests a very broken dynamic page definition
 */
class FooAjaxController
{
    use AjaxControllerTrait;
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function get($id)
    {
        return $this->container->get($id);
    }
}
