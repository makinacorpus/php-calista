<?php

namespace MakinaCorpus\Dashboard\Drupal\Datasource\QueryExtender;

use MakinaCorpus\Dashboard\Datasource\Configuration;

/**
 * Query extender for Drupal paging that will override the element using
 * the one you have in the page state object.
 *
 * @codeCoverageIgnore
 */
class DrupalPager extends \SelectQueryExtender
{
    private $state;
    private $customCountQuery;

    /**
     * Default constructor
     *
     * @param \SelectQueryInterface $query
     * @param \DatabaseConnection $connection
     */
    public function __construct(\SelectQueryInterface $query, \DatabaseConnection $connection)
    {
        parent::__construct($query, $connection);

        $this->addTag('pager');
    }

    /**
     * Override the execute method.
     *
     * Before we run the query, we need to add pager-based range() instructions
     * to it.
     */
    public function execute()
    {
        if (!$this->preExecute($this)) {
            return;
        }

        // Our custom pager starts with 1 but Drupal starts with 0, we need
        // to apply a delta over the page offset to restore this correctly.
        // But we also another problem, we need to know if the page was set
        // in the query, else the default 1 - 0 + 1 will become a positive
        // offset instead of going to page 0.
        // Note that this makes those datasources not compatible with a custom
        // user-forged request as input...
        $limit  = $this->state->getLimit();
        $offset = $this->state->getOffset();

        $this->state->setTotalItemCount($this->getCountQuery()->execute()->fetchField());
        $this->range($offset, $limit);

        // Count query has run, now run the query normally.
        return $this->query->execute();
    }

    /**
     * Specify a custom count query if necessary
     *
     * @param \SelectQueryInterface $query
     *
     * @return $this
     */
    public function setCountQuery(\SelectQueryInterface $query)
    {
        $this->customCountQuery = $query;

        return $this;
    }

    /**
     * Get count query
     *
     * @return \SelectQueryInterface
     */
    public function getCountQuery()
    {
        return $this->customCountQuery ? $this->customCountQuery : $this->query->countQuery();
    }

    /**
     * Set page state
     *
     * @return $this
     */
    public function setState(Configuration $state)
    {
        $this->state = $state;

        return $this;
    }
}
