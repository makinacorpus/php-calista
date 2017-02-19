<?php

namespace MakinaCorpus\Drupal\Dashboard\Portlet;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base implementation for portlet that will handle boring stuff for you and
 * just give you the chance to operate over the internal page builder options
 * and let you choose the page and data template display.
 */
abstract class AbstractPortlet implements PortletInterface
{
    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * @var PageBuilder
     */
    private $pageBuilder;

    /**
     * {@inheritdoc}
     */
    public function setAccount(AccountInterface $account)
    {
        $this->account = $account;
    }

    /**
     * Set page builder
     *
     * A fresh new page builder will be given to the portlet thanks to a
     * dependency injection pass, which means that the user will always
     * work an empty non-shared instance.
     *
     * @param PageBuilder $pageBuilder
     */
    final public function setPageBuilder(PageBuilder $pageBuilder)
    {
        $this->pageBuilder = $pageBuilder;
        $this->pageBuilder->hideFilters()->hideSearch()->hideSort();
    }

    /**
     * Get current account
     *
     * @return AccountInterface
     */
    final protected function getAccount()
    {
        return $this->account;
    }

    /**
     * Create page builder
     *
     * The instance given from here will be an empty non-shared instance.
     *
     * @param PageBuilder $pageBuilder
     */
    abstract protected function createPage(PageBuilder $pageBuilder);

    /**
     * {@inheritDoc}
     */
    final public function getContent()
    {
        try {
            $this->createPage($this->pageBuilder);

            return $this->pageBuilder->searchAndRender(new Request());

        } catch (\Exception $e) {

            // @todo log me !!!!

            // Régis doesn't like when Elastic is down. Elastic does business
            // stuff therefore any business-critical component failing should
            // never be caught, but without this main dashboard page might
            // not be reachable at all
            return $e->getMessage() . '<br/><pre>' . $e->getTraceAsString() . '</pre>';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getActions()
    {
        return [];
    }
}
