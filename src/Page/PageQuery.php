<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

use Symfony\Component\HttpFoundation\Request;

/**
 * Parses and cleanups the incomming query
 */
class PageQuery
{
    /**
     * Parsed search string query
     *
     * @var string[]
     */
    private $search = [];

    /**
     * Cleanup and ready to use query, without the parsed search query string
     *
     * @var string[]
     */
    private $query = [];

    /**
     * Full containing all query string for datasource
     *
     * @var array
     */
    private $all = [];

    /**
     * Default constructor
     *
     * @param Request $request
     * @param string $searchParam
     * @param bool $parseSearch
     * @param array $baseQuery
     */
    public function __construct(Request $request, $searchParam, $parseSearch = false, array $baseQuery = [])
    {
        // Parsed the search query string if asked for
        if ($parseSearch) {
            $this->search = $this->parseSearchQueryString($request, $searchParam);
            $this->search = $this->filterQueryWithBaseQuery($this->search, $baseQuery);
        }

        // Append filter values from the request into the query
        $this->query = $this->createQueryFromRequest($request, [$searchParam, 'q']);

        // Cleanup request and query string input, normalize using allowed
        // values from current datasource filter and base query
        if ($baseQuery) {
            $this->query = $this->filterQueryWithBaseQuery($this->query, $baseQuery);
        }

        if ($this->search) {
            $this->all = $this->mergeQueries([
                $this->search,
                // Do not include the raw full text search parameter from the
                // initial query array, because is has been parsed and reduced,
                // only keep the one from the search array
                array_diff_key(
                    $this->query,
                    [$searchParam => null]
                )
            ]);
        } else {
            $this->all = $this->query;
        }

        // This is a very specific use case, but datasource awaits for a single
        // query string for full text search, so just flatten this query
        // parameter
        $this->all    = $this->flattenQueryParam($this->all, $searchParam);
        $this->search = $this->flattenQueryParam($this->search, $searchParam);
        $this->query  = $this->flattenQueryParam($this->query, $searchParam);
    }

    /**
     * Get the complete incomming query
     *
     * @return array
     */
    public function getAll()
    {
        return $this->all;
    }

    /**
     * Get the query without the parsed query string
     *
     * @return array
     */
    public function getRouteParameters()
    {
        return $this->query;
    }

    /**
     * In the given query, flatten the given parameter
     *
     * @param array $query
     * @param string $param
     */
    private function flattenQueryParam($query, $param)
    {
        if (isset($query[$param]) && is_array($query[$param])) {
            $query[$param] = implode(' ', $query[$param]);
        }

        return $query;
    }

    /**
     * Merge all queries altogether
     *
     * @param array[][] $queries
     *   Array of queries to merge
     *
     * @return string[][]
     *   Merged queries
     */
    private function mergeQueries(array $queries)
    {
        $ret = [];

        foreach ($queries as $query) {
            foreach ($query as $field => $values) {

                // Normalize all values to arrays
                if (!is_array($values)) {
                    $values = [$values];
                }

                // If value already exists in ret, merge it with the new values
                // and drop the duplicated values altogether
                if (isset($ret[$field])) {
                    $ret[$field] = array_unique(array_merge($ret[$field], $values));
                } else {
                    $ret[$field] = array_unique($values);
                }
            }
        }

        return $ret;
    }

    /**
     * From the incoming query, prepare the $query array for datasource
     *
     * @param Request $request
     *   Incoming request
     *
     * @return string[]
     *   Prepare query parameters, using base query and filters
     */
    private function createQueryFromRequest(Request $request, $searchParam)
    {
        $query = array_merge(
            $request->query->all(),
            $request->attributes->get('_route_params', [])
        );

        // We are working with Drupal, q should never get here.
        unset($query['q']);

        // @todo ugly
        $query = Filter::fixQuery($query);

        // Drops all empty values
        $query = array_filter($query, function ($value) {
            return $value !== '' && $value !== null;
        });

        return $query;
    }

    /**
     * Parse incomming search query string
     *
     * @param Request $request
     *   Incoming request
     * @param string $param
     *   Search parameter name
     *
     * @return string[]
     *   Prepare query parameters, using query string
     */
    private function parseSearchQueryString(Request $request, $param)
    {
        return (new QueryStringParser())->parse($request->get($param, ''), $param);
    }

    /**
     * From the given prepared but unfiltered query, drop all values that are
     * not in base query boundaries
     *
     * @param array $query
     * @param array $baseQuery
     *
     * @return array $query
     */
    private function filterQueryWithBaseQuery(array $query, array $baseQuery)
    {
        // Ensure that query values are in base query bounds
        // @todo find a more generic and proper way to do this
        foreach ($baseQuery as $name => $allowed) {
            if (isset($query[$name])) {
                $input = $query[$name];
                // Normalize
                if (!is_array($allowed)) {
                    $allowed = [$allowed];
                }
                if (!is_array($input)) {
                    $input = [$input];
                }
                // Restrict to fixed bounds
                $query[$name] = array_unique(array_intersect($input, $allowed));
            }
        }

        return $query;
    }
}
