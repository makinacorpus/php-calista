# TODO list

 *  [_] action: handle redirect/destination in a generic fashion (and add query params)
 *  [_] action: make it less verbose to register
 *  [_] action: make it less verbose to write
 *  [_] action: rendering is terribly slow, make it better
 *  [_] all: performance: possibility of caching options/skipping resolver
 *  [_] context: better default actions display
 *  [_] context: better pane display
 *  [_] context: get the Context namespace out of the Drupal namespace
 *  [_] datasource: create a default drupal database datasouce with schema introspection
 *  [_] drupal, controller: access check (csrf?) for ajax refresh
 *  [_] drupal, view: expose a view mode for configuring display
 *  [_] drupal, view: expose view mode in property view options
 *  [_] drupal, view: handle gracefully fields
 *  [_] input, query: allow some filters to be virtual (not displayed)
 *  [_] page: allow annotations for callbacks, without defining the properties
 *  [_] page: allow annotations for properties
 *  [_] view, twig: ajax refresh looses base query when rendering filters
 *  [_] view, twig: ajax refresh looses custom input options when rendering
 *  [_] view, twig: allow to register renderers for specific classes or (class, property) couples
 *  [_] view: filters whose values are selected by base query should not be visually selected
 *  [_] view: for-else in default templates, page builder "empty text" property
 *  [_] write unit tests for the Action namespace
 *  [_] write unit tests for the Context namespace
 *  [_] write unit tests for the Util namespace
 *  [x] action: get the action rendering out of Drupal API
 *  [x] action: when rendered via ajax, destination parameter is broken
 *  [x] all: change module name to allow it to work aside the 1.x version
 *  [x] all: change namespace to allow it to work aside the 1.x version
 *  [x] all: find a new name for core/decoupled library
 *  [x] all: once name is found, export it to its own package
 *  [x] datasource: allow datasource disovery via the view factory
 *  [x] datasource: PropertyInfo for the Datasources
 *  [x] input, query: remove base query values from filter
 *  [x] input, query: when using base query, filtering in base query range is ignored
 *  [x] page, view: allow virtual properties
 *  [x] page, view: refine virtual, non existing types does not necesarily means virtual
 *  [x] page, view: refine virtual, real virtual properties should not use property accessor at all
 *  [x] page: make it less verbose to register
 *  [x] portlet: re-introduce the portlet api and dashboard page (done in Drupal module)
 *  [x] view, twig: if pager is not present initially, refresh will not add it if necessary
 *  [x] view, twig: when sort is disabled in view definition, sorts disaplays anyway
 *  [x] view: fix the pager
 *  [x] view: PropertyAccess for the Page renderer
