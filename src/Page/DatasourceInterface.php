<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

/**
 * Use this class to interface with the main dashboard page layout
 *
 * You won't need to care about rendering or layout, just implement this in
 * order to expose your data.
 *
 * @see \MakinaCorpus\Drupal\Dashboard\Page\Node\DefaultNodeDatasource
 *   For a complete working exemple (which was the original prototype)
 */
interface DatasourceInterface
{
    /**
     * Get ready to display filters
     *
     * @param string[] $query
     *   Incoming query parameters
     *
     * @return Filter[]
     *   Keys does not matter, while values should be render arrays
     */
    public function getFilters($query);

    /**
     * Get sort fields
     *
     * @param string[] $query
     *   Incoming query parameters
     *
     * @return string[]
     *   Keys are field names, values are human readable labels
     */
    public function getSortFields($query);

    /**
     * Get default sort
     *
     * @return string[]
     *   First value is sort field, second is sort order,
     *   if first value is null, first in the list will be the default,
     *   if seconf value is null, default is descending
     *   if the whole return is null, all is default
     */
    public function getDefaultSort();

    /**
     * This method is called before all others, if some operations such as the
     * filters building needing a request to the backend, then this is the place
     * where you should probably do it
     *
     * @param string[] $query
     *   Incoming query parameters
     * @param string[] $filter
     *   Base query (filtering query) set by the caller
     */
    public function init(array $query, array $filter);

    /**
     * Get items to display
     *
     * This should NOT return rendered items but loaded items or item
     * identifiers depending upon your implementation: only the Display
     * instance will really display items, since it may change the display
     * depending upon current context
     *
     * @param string[] $query
     *   Incoming query parameters
     * @param PageState $pageState
     *   Current page state
     *
     * @return mixed[]
     */
    public function getItems($query, PageState $pageState);

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
     * @param string[] $query
     *   Incoming query parameters
     * @param string[] $idList
     *   Arbitrary item identifier list
     *
     * @return bool
     */
    public function validateItems(array $query, array $idList);
}
