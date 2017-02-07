<?php

namespace MakinaCorpus\Drupal\Dashboard\Portlet;

use Drupal\Core\Session\AccountInterface;

abstract class AbstractPortlet implements PortletInterface
{
    /**
     * @var AccountInterface
     */
    private $account;

    /**
     * {@inheritdoc}
     */
    public function setAccount(AccountInterface $account)
    {
        $this->account = $account;
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
     * {@inheritDoc}
     */
    public function getActions()
    {
        return [];
    }
}
