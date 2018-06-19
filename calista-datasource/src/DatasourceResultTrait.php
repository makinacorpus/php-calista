<?php

namespace MakinaCorpus\Calista\Datasource;

use MakinaCorpus\Calista\Query\Query;

/**
 * Basics for result iterators
 *
 * @codeCoverageIgnore
 */
trait DatasourceResultTrait /* implements DatasourceResultInterface */
{
    private $properties = [];
    private $totalCount;

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return $this->itemClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function canStream()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalItemCount($count)
    {
        $this->totalCount = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTotalItemCount()
    {
        return null !== $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageCount($limit = Query::LIMIT_DEFAULT)
    {
        return null !== $this->totalCount ? ceil($this->totalCount / $limit) : 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageRange($page = 1, $limit = Query::LIMIT_DEFAULT)
    {
        $num = ceil($this->getTotalCount() / $limit);
        $min = max([$page - 2, 1]);
        $max = min([$page + 2, $num]);

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
