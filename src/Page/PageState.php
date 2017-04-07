<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

/**
 * Contains the current page state
 *
 * @codeCoverageIgnore
 */
class PageState
{
    const SORT_DESC = 'desc';
    const SORT_ASC = 'asc';
    const LIMIT_DEFAULT = 50;

    private $searchParameter = null;
    private $currentSearch;
    private $currentDisplay = null;
    private $sortField = null;
    private $sortOrder = self::SORT_DESC;
    private $limit = null;
    private $page = 1;
    private $pagerElement = 0;
    private $pageParameter = 'page';
    private $totalCount = null;

    public function setSearchParameter($searchParameter)
    {
        $this->searchParameter = $searchParameter;
    }

    public function getSearchParameter()
    {
        return $this->searchParameter;
    }

    public function setCurrentDisplay($currentDisplay)
    {
        $this->currentDisplay = $currentDisplay;
    }

    public function getCurrentDisplay()
    {
        return $this->currentDisplay;
    }

    public function setCurrentSearch($searchString)
    {
        $this->currentSearch = $searchString;
    }

    public function getCurrentSearch()
    {
        return $this->currentSearch;
    }

    public function setTotalItemCount($count)
    {
        $this->totalCount = $count;
    }

    public function hasTotalItemCount()
    {
        return null !== $this->totalCount;
    }

    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @deprecated
     */
    public function getTotalItemCount()
    {
        return $this->totalCount;
    }

    public function setPageParameter($param)
    {
        $this->pageParameter = $param;
    }

    public function getPageParameter()
    {
        return $this->pageParameter;
    }

    public function setPagerElement($element)
    {
        $this->pagerElement = $element;
    }

    public function getPagerElement()
    {
        return $this->pagerElement;
    }

    public function setSortField($field)
    {
        $this->sortField = $field;
    }

    public function hasSortField()
    {
        return $this->sortField;
    }

    public function getSortField()
    {
        return $this->sortField;
    }

    public function setSortOrder($order)
    {
        $this->sortOrder = $order;
    }

    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    public function setRange($limit, $page = 1)
    {
        $this->limit = $limit;
        $this->page = $page;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getOffset()
    {
        return $this->limit * max([0, $this->page - 1]);
    }

    public function getPageNumber()
    {
        return $this->page;
    }

    public function getPageCount()
    {
        return ceil($this->totalCount / $this->limit);
    }

    public function getPageRange()
    {
        $num = $this->getPageCount();
        $min = max([$this->page - 2, 1]);
        $max = min([$this->page + 2, $num]);

        if ($max - $min < 4) {
            if (1 == $min) {
                return range(1, min([5, $num]));
            } else {
                return range(max([$num - 4, 1]), $num);
            }
        } else {
            return range($min, $max);
        }
    }
}
