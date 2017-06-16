<?php

namespace MakinaCorpus\Dashboard\Datasource;

use MakinaCorpus\Dashboard\Page\Filter;
use MakinaCorpus\Dashboard\Page\SortCollection;

/**
 * Use this class to interface with the main dashboard page layout
 *
 * You won't need to care about rendering or layout, just implement this in
 * order to expose your data.
 *
 * @see \MakinaCorpus\Dashboard\Datasource\Node\DefaultNodeDatasource
 *   For a complete working exemple (which was the original prototype)
 */
interface DatasourceInterface
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
     * Get ready to display filters
     *
     * @param Query $query
     *   Incoming query parameters
     *
     * @return Filter[]
     *   Keys does not matter, while values should be render arrays
     */
    public function getFilters(Query $query);

    /**
     * Get sort fields
     *
     * @param Query $query
     *   Incoming query parameters
     *
     * @return SortCollection
     */
    public function getSorts(Query $query);

    /**
     * This method is called before all others, if some operations such as the
     * filters building needing a request to the backend, then this is the place
     * where you should probably do it
     *
     * @param Query $query
     *   Incoming query parameters
     */
    public function init(Query $query);

    /**
     * Get items to display
     *
     * This should NOT return rendered items but loaded items or item
     * identifiers depending upon your implementation: only the Display
     * instance will really display items, since it may change the display
     * depending upon current context
     *
     * @param Query $query
     *   Incoming query
     *
     * @return DatasourceResultInterface
     */
    public function getItems(Query $query);

    /**
     * Given an arbitrary list of identifiers that this datasource should
     * understand, return false if any of the given item identifiers are part
     * of this datasource data set.
     *
     * Item identifiers are given in an arbitrary fashion, the datasource might
     * not even understand the concept of identifiers.
     *
     * This can be used by external code to implement complex form widget using
     * administration screens as item selectors, for example, but this module
     * does not care about it.
     *
     * @param Query $query
     *   Incoming query
     * @param string[] $idList
     *   Arbitrary item identifier list
     *
     * @return bool
     */
    public function validateItems(Query $query, array $idList);
}
