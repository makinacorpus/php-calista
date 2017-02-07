<?php

namespace MakinaCorpus\Drupal\Dashboard\Portlet;

use MakinaCorpus\Drupal\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Portlet implementation using a datasource and a page type.
 */
class PagePortlet extends AbstractPortlet
{
    /**
     * @var DatasourceInterface
     */
    private $datasource;

    /**
     * @var PageBuilder
     */
    private $pageBuilder;

    /**
     * @var string
     */
    private $name;

    /**
     * Default constructor
     *
     * @param DatasourceInterface $datasource
     * @param PageBuilder $page
     */
    public function __construct(DatasourceInterface $datasource, PageBuilder $pageBuilder)
    {
        $this->datasource = $datasource;
        $this->pageBuilder = $pageBuilder;
    }

    /**
     * Get page (datasource) base filter query
     *
     * @return array
     */
    protected function getBaseQuery()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        try {
            return $this->pageBuilder->setBaseQuery($this->getBaseQuery())->searchAndRender(new Request());

        } catch (\Exception $e) {

            // @todo log me !!!!

            // Régis doesn't like when Elastic is down. Elastic does business
            // stuff therefore any business-critical component failing should
            // never be caught, but without this main dashboard page might
            // not be reachable at all
            return $e->getMessage() . '<br/><pre>' . $e->getTraceAsString() . '</pre>';
        }
    }
}
