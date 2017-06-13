<?php

namespace MakinaCorpus\Dashboard\Datasource;

/**
 * Configuration for the query
 *
 * @codeCoverageIgnore
 */
class Configuration
{
    private $defaultLimit = Query::LIMIT_DEFAULT;
    private $displayParameter = 'disp';
    private $doParseSearch = false;
    private $isLimitFixed = true;
    private $limitParameter = 'limit';
    private $pageParameter = 'page';
    private $pagerElement = 0;
    private $searchParameter = null;
    private $sortFieldParameter = 'st';
    private $sortOrderParameter = 'by';

    public function enableParseSearch()
    {
        $this->doParseSearch = true;
    }

    public function disableParseSearch()
    {
        $this->doParseSearch = false;
    }

    public function doParseSearch()
    {
        return $this->doParseSearch;
    }

    public function allowLimitChange()
    {
        $this->isLimitFixed = false;
    }

    public function disallowLimitChange()
    {
        $this->isLimitFixed = true;
    }

    public function isLimitFixed()
    {
       return $this->isLimitFixed;
    }

    public function setLimitParameter($name)
    {
        $this->limitParameter = $name;
    }

    public function getLimitParameter()
    {
        return $this->limitParameter;
    }

    public function setDefaultLimit($limit)
    {
        $this->defaultLimit = $limit;
    }

    public function getDefaultLimit()
    {
        return $this->defaultLimit;
    }

    public function setSortFieldParameter($name)
    {
        $this->sortFieldParameter = $name;
    }

    public function getSortFieldParameter()
    {
        return $this->sortFieldParameter;
    }

    public function setSortOrderParameter($name)
    {
        $this->sortOrderParameter = $name;
    }

    public function getSortOrderParameter()
    {
        return $this->sortOrderParameter;
    }

    public function isSearchEnabled()
    {
        return !!$this->searchParameter;
    }

    public function setSearchParameter($name)
    {
        $this->searchParameter = $name;
    }

    public function getSearchParameter()
    {
        return $this->searchParameter;
    }

    public function setDisplayParameter($name)
    {
        $this->displayParameter = $name;
    }

    public function getDisplayParameter()
    {
        return $this->displayParameter;
    }

    public function setPageParameter($name)
    {
        $this->pageParameter = $name;
    }

    public function getPageParameter()
    {
        return $this->pageParameter;
    }

    public function setPagerElement($name)
    {
        $this->pagerElement = $name;
    }

    public function getPagerElement()
    {
        return $this->pagerElement;
    }
}
