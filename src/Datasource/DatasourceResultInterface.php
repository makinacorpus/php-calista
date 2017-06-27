<?php

namespace MakinaCorpus\Calista\Datasource;

/**
 * Result iterator interface
 *
 * count() method will return the current item batch items, depending upon the
 * query range.
 */
interface DatasourceResultInterface extends \Traversable, \Countable
{
    /**
     * Get item class
     *
     * Item class will enable the ProperyInfo component usage over your objects.
     * Whenever you have very specific classes you also should write your own
     * property extractors.
     *
     * @return null|string
     */
    public function getItemClass();

    /**
     * Can this datasource stream large datasets
     *
     * Most result iterators should never preload items, and should allow items
     * to be iterated with large datasets without compromising the PHP memory
     * consumption, nevertheless, some might not be able to do this, case in
     * which this method should return false to indicate other developers this
     * must not be used for things like data to file export/streaming.
     *
     * @return bool
     */
    public function canStream();

    /**
     * Get total item count
     *
     * @param int $count
     */
    public function setTotalItemCount($count);

    /**
     * Did the datasource provided an item count
     *
     * @return bool
     */
    public function hasTotalItemCount();

    /**
     * Get total item count
     *
     * @return int
     */
    public function getTotalCount();

    /**
     * Compute the current page range
     *
     * @param int $page
     *   Relative int to compute pages from
     *
     * @return int[]
     */
    public function getPageRange($page = 1);
}
