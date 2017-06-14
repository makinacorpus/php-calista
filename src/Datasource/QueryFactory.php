<?php

namespace MakinaCorpus\Dashboard\Datasource;

use Symfony\Component\HttpFoundation\Request;

/**
 * Parses and cleanups the incomming query from a Symfony request
 */
class QueryFactory
{
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
    public function fromArray(Configuration $configuration, array $input, array $baseQuery = [], $route = null)
    {
        $rawSearchString = '';
        $searchParameter = $configuration->getSearchParameter();

        // Append filter values from the request into the query
        $input = $this->normalizeInput($input);

        // We'll start with route parameters being identical that the global
        // query, we will prune default values later to make it shorter
        $routeParameters = $input;

        // Deal with search
        if ($configuration->isSearchEnabled() && $searchParameter && !empty($input[$searchParameter])) {
            $rawSearchString = $input[$searchParameter];

            // Parse search and merge it properly to the incomming query
            if ($configuration->isSearchParsed()) {
                $parsedSearch = (new QueryStringParser())->parse($rawSearchString, $searchParameter);

                if ($parsedSearch) {
                    // Filters should not contain the search parameter, since
                    // it has been parsed and normalize, we remove it then merge
                    // the parsed one
                    unset($input[$searchParameter]);
                    $input = $this->mergeQueries([$parsedSearch, $input]);
                }
            }
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
            $this->flattenQuery($this->applyBaseQuery($input, $baseQuery), [$searchParameter]),
            $this->flattenQuery($this->applyBaseQuery($routeParameters, $baseQuery), [$searchParameter]),
            $baseQuery
        );
    }

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
        $input = array_merge($request->query->all(), $request->attributes->get('_route_params', []));

        return $this->fromArray($configuration, $input, $baseQuery, $route);
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
     * @param string[]|string[][]
     *   Input
     *
     * @return string[]|string[][]
     *   Prepare query parameters, using base query and filters
     */
    private function normalizeInput(array $query, array $exclude = ['q'])
    {
        // Proceed to unwanted parameters exclusion
        foreach ($exclude as $parameter) {
            unset($query[$parameter]);
        }

        // Normalize input
        foreach ($query as $key => $value) {
            // Drops all empty values
            if ('' === $value || null === $value || [] === $value) {
                unset($query[$key]);
                continue;
            }
            // Normalize non-array input using the value separator
            if (is_string($value) && false !== strpos($value, Query::URL_VALUE_SEP)) {
                $query[$key] = explode(Query::URL_VALUE_SEP, $value);
            }
        }

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
