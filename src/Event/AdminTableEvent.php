<?php

namespace MakinaCorpus\Calista\Event;

use MakinaCorpus\Calista\Util\AdminTable;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * An admin information table is being displayed, append stuff in there
 *
 * @codeCoverageIgnore
 */
class AdminTableEvent extends GenericEvent
{
    /**
     * Default constructor
     *
     * @param AdminTable $table
     */
    public function __construct(AdminTable $table)
    {
        parent::__construct($table);
    }

    /**
     * Get table
     *
     * @return AdminTable
     */
    public function getTable()
    {
        return $this->getSubject();
    }
}
