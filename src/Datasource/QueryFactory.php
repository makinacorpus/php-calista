<?php

namespace MakinaCorpus\Dashboard\Datasource;

use Symfony\Component\HttpFoundation\Request;

/**
 * Parses and cleanups the incomming query from a Symfony request
 */
class QueryFactory
{
    /**
     * Create query from request
     *
     * @param Configuration $configuration
     *   Current search configuration
     * @param Request $request
     *   Incomming request
     * @param string[] $baseQuery
     *   Base filter query
     *
     * @return Query
     */
    public function fromRequest(Configuration $configuration, Request $request, array $baseQuery = [])
    {
        $route = $request->attributes->get('_route');
        $parsedSearch = [];
        $rawSearchString = '';
        $searchParameter = $configuration->getSearchParameter();

        // Append filter values from the request into the query
        $filters = $this->createQueryFromRequest($request);
        $routeParameters = $filters;

        if ($configuration->getSearchParameter()) {
            $rawSearchString = $request->get($searchParameter, '');

            // Parsed the search query string if asked for
            if ($rawSearchString && $configuration->doParseSearch()) {
                $parsedSearch = (new QueryStringParser())->parse($rawSearchString, $searchParameter);

                if ($parsedSearch) {
                    unset($filters[$searchParameter]);
                    $filters = $this->mergeQueries([$parsedSearch, $filters]);
                }
            }
        }

        // Parameters that are not in the filter array, but present in base
        // query must be added into filters
        if ($baseQuery) {
            $filters = $this->mergeQueries([$filters]);
        }

        // Route parameters must contain the raw search string and not the
        // parsed search string to be able to rebuild correctly links
        if ($rawSearchString) {
            $routeParameters[$searchParameter] = $rawSearchString;
        }

        // This is a very specific use case, but datasource awaits for a single
        // query string for full text search, so just flatten this query
        // parameter
        return new Query(
            $configuration,
            $route,
            $this->flattenQuery($this->applyBaseQuery($filters, $baseQuery), [$searchParameter]),
            $this->flattenQuery($this->applyBaseQuery($routeParameters, $baseQuery), [$searchParameter]),
            $baseQuery
        );
    }

    /**
     * Create query from array
     *
     * @param Configuration $configuration
     *   Current search configuration
     * @param array $request
     *   Incomming request
     * @param string[] $baseQuery
     *   Base filter query
     *
     * @return Query
     */
    public function fromArray(Configuration $configuration, array $request, array $baseQuery = [], $route = null)
    {
        $request = new Request($request, [], ['_route' => $route]);

        return $this->fromRequest($configuration, $request, $baseQuery);
    }

    /**
     * In the given query, flatten the given parameter.
     *
     * All values in the query that are arrays with a single value will be
     * flattened to be a value instead of an array, this way we limit the
     * potential wrong type conversions with special parameters such as the
     * page number.
     *
     * All parameters in the $needsImplode array will be imploded using a
     * whitespace, this is useful for the full text search parameter, that
     * needs to remain a single string.
     *
     * @param array $query
     * @param string[] $needsImplode
     */
    private function flattenQuery($query, array $needsImplode = [])
    {
        foreach ($query as $key => $values) {
            if (is_array($values)) {
                if (1 === count($values)) {
                    $query[$key] = reset($values);
                } else if (in_array($key, $needsImplode)) {
                    $query[$key] = implode(' ', $values);
                }
            }
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
    private function createQueryFromRequest(Request $request)
    {
        $query = array_merge(
            $request->query->all(),
            $request->attributes->get('_route_params', [])
        );

        // We are working with Drupal, q should never get here.
        unset($query['q']);

        // @todo ugly
        foreach ($query as $key => $value) {
            if (is_string($value) && false !== strpos($value, Query::URL_VALUE_SEP)) {
                $query[$key] = explode(Query::URL_VALUE_SEP, $value);
            }
        }

        // Drops all empty values
        $query = array_filter($query, function ($value) {
            return $value !== '' && $value !== null;
        });

        return $query;
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
    private function applyBaseQuery(array $query, array $baseQuery)
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
            } else {
                $query[$name] = $allowed;
            }
        }

        return $query;
    }
}
