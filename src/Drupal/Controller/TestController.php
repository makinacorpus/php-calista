<?php

namespace MakinaCorpus\Dashboard\Drupal\Controller;

use MakinaCorpus\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Dashboard\Drupal\Page\AccountPageDefinition;
use MakinaCorpus\Dashboard\Drupal\Page\NodePageDefinition;
use MakinaCorpus\Drupal\Sf\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Action processor controller
 */
class TestController extends Controller
{
    use PageControllerTrait;

    /**
     * List all nodes
     */
    public function listNodesAction(Request $request)
    {
        return $this->renderPage(NodePageDefinition::class, $request);
    }

    /**
     * List all users
     */
    public function listAccountsAction(Request $request)
    {
        return $this->renderPage(AccountPageDefinition::class, $request);
    }
}
