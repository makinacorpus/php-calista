<?php

namespace MakinaCorpus\Dashboard\Datasource;

/**
 * Basics for result iterators
 *
 * @codeCoverageIgnore
 */
trait DatasourceResultTrait /* implements DatasourceResultInterface */
{
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
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * Compute the current page range
     *
     * @return int[]
     */
    public function getPageRange($page = 1)
    {
        $num = $this->count();
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
