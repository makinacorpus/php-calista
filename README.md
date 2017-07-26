# Calista - Backend agnostic API for building user interface or data exports

Complete API for building entity lists using configuration without writing
boilerplate code, necessiting the bare minimum code, i.e. the datasource
for integrating with various data providers.

In other words, this is a backend-independent dynamic and configuration based
object list streamer and renderer that may help you with those use case:

 *  a way to execute datasources using a user query and fetch items;
 *  a way to provide administration or front-end object listings;
 *  a way to provide data export (CSV, Excel, Rest...);
 *  and probably more.


# Documentation

## Architected basics

This API decouples data model, i.e. the **Datasource** from the data presentation
i.e. the **View**, but also how incomming requests are interpreted, the controller
part of the API.


### From input to datasource

 *  The **datasource** fetches data and provides metadata about the possible
    **filters**, **sort fields** and its own capabilities;

 *  The datasource needs a **query** to run, the query is a sanitized, processed
    representation of the incomming **request**;

 *  Query sanitization is done using user configuration, carried by the
    **input definition**.

Example that would display a custom template with datasource results:

```php
use MakinaCorpus\Calista\Datasource\InputDefinition;
use MakinaCorpus\Calista\Datasource\QueryFactory;
use MyApp\SomeDatasource;
use Symfony\Component\HttpFoundation\Request;

function getResult(Request $request) {
    // Datasource
    $datasource = new SomeDatasource();

    // Input creation, needs the datasource for filtering input depending
    // upon its capabilities
    $inputDefinition = new InputDefinition($datasource, [
        'base_query'          => ['type' => 'article'],
        'limit_allowed'       => false,
        'limit_default'       => 100,
        'pager_enable'        => true,
        'pager_param'         => 'page',
        'search_enable'       => false,
        'sort_default_field'  => 'created_at',
        'sort_default_order'  => Query::SORT_DESC,
    ]);

    // Create the sanitized query
    $query = (new QueryFactory())->fromRequest($inputDefinition, $request);

    // Result is an iterable of objects whose class is defined by the
    // SomeDatasource::getItemClass() method
    $result = $datasource->getItems($query);

    return [$query, $result];
}

function myControllerAction(Request $request) {
    list($query, $result) = getResult($request);

    return $this->render('some_template.html.twig', ['items' => $result]);
}
```


### From the result to the view

 *  From a **view definition** the **view** returns either a string or a Symfony
    Response object;

 *  It uses the **property renderer** to display each result item properties;

 *  Property renderer relies upon the **property access** and **property info**
    Symfony components to find and display data;

 *  Property renderer carries its own logic for rendering and display, but may
    be extended using rendering callbacks.

Example, let's complete the code above that will stream a CSV file:
```php
use MakinaCorpus\Calista\View\PropertyRenderer;
use MakinaCorpus\Calista\View\Stream\CsvStreamView;

function myControllerCsvExportAction(Request $request) {
    list($query, $result) = getResult($request);

    // Property renderer should be created by your framework
    $propertyRenderer = new PropertyRenderer(/* Symfony property access and info components */);

    // Input creation, needs the datasource for filtering input depending
    // upon its capabilities
    $viewDefinition = new ViewDefinition([
        'extra' => [
            'add_bom' => true,
            'add_header' => true,
        ],
        'properties' => [
            'id' => [
                'label' => 'Identifier',
                'type' => 'int',
                'decimal_separator' => '',
            ],
            'title' => [
                'label' => 'Title',
                'type' => 'string',
                'string_maxlength' => null,
            ],
            'city' => [
                'label' => 'City',
                'type' => 'string',
                'string_maxlength' => null,
            ],
            'date' => [
                'label' => 'Event date',
                'type' => 'int',
                'callback' => 'renderDate',
                'date_format' => 'd/m/Y',
            ],
            'comment' => [
                'label' => 'Comment',
                'type' => 'string',
                'string_maxlength' => null,
            ],
        ],
        'view_type' => 'csv_stream',
    ]);

    $view = new CsvStreamView($propertyRenderer);

    return $view->renderAsResponse($viewDefinition, $result, $query);
}

```


### In a few words

If you understood the minimal examples above, you understand pretty much
everything of this API, in a few words we could synthetize like this:

 *  user defines the input definition, which is a basic input sanitization,

 *  user defines a view definition, which carries the properties that should be
    displayed, and how they should displayed,

 *  user executes a datasource using the sanitized query thanks to the
    configured input,

 *  then ask the view to render it using the view definition.


## Integration with Symfony

@todo


## Documentation todo

 *  @todo datasource
 *  @todo input definition + datasource usage
 *  @todo view definition
 *  @todo input definition + datasource + view definition usage
 *  @todo page definition
 *  @todo dynamic page definition
 *  @todo config page definition
 *  @todo config page definition as framework configuration
 *  @todo redirect mecanism
 *  @todo action providers
