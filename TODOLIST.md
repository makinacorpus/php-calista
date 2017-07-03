# TODO list

 *  [ ] action: make it less verbose to register
 *  [ ] action: make it less verbose to write
 *  [ ] action: rendering is terribly slow, make it better
 *  [ ] all: performance: possibility of caching options/skipping resolver
 *  [ ] input, query: allow some filters to be virtual (not displayed)
 *  [ ] page: allow annotations for callbacks, without defining the properties
 *  [ ] page: allow annotations for properties
 *  [ ] view, twig: allow to register renderers for specific classes or (class, property) couples
 *  [ ] view: for-else in default templates, page builder "empty text" property
 *  [ ] write unit tests for the Action namespace
 *  [ ] write unit tests for the Context namespace
 *  [ ] write unit tests for the Util namespace
 *  [x] action: get the action rendering out of Drupal API
 *  [x] action: handle redirect/destination in a generic fashion (and add query params)
 *  [x] action: when rendered via ajax, destination parameter is broken
 *  [x] all: change module name to allow it to work aside the 1.x version
 *  [x] all: change namespace to allow it to work aside the 1.x version
 *  [x] all: find a new name for core/decoupled library
 *  [x] all: once name is found, export it to its own package
 *  [x] datasource: allow datasource disovery via the view factory
 *  [x] datasource: PropertyInfo for the Datasources
 *  [x] drupal, controller: access check using token in session for ajax refresh
 *  [x] input, query: remove base query values from filter
 *  [x] input, query: when using base query, filtering in base query range is ignored
 *  [x] page, view: allow virtual properties
 *  [x] page, view: refine virtual, non existing types does not necesarily means virtual
 *  [x] page, view: refine virtual, real virtual properties should not use property accessor at all
 *  [x] page: make it less verbose to register
 *  [x] portlet: re-introduce the portlet api and dashboard page (done in Drupal module)
 *  [x] view, twig: ajax refresh looses base query when rendering filters
 *  [x] view, twig: ajax refresh looses custom input options when rendering
 *  [x] view, twig: history push ajax refreshes
 *  [x] view, twig: if pager is not present initially, refresh will not add it if necessary
 *  [x] view, twig: when sort is disabled in view definition, sorts disaplays anyway
 *  [x] view: filters whose values are selected by base query should not be visually selected
 *  [x] view: fix the pager
 *  [x] view: PropertyAccess for the Page renderer
