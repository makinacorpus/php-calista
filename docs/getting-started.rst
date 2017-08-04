Getting started
===============

This API can be used without the Symfony fullstack framework, nevertheless using
it is the easiest way to start and learn.

This guide postulates that you have a Symfony fullstack application up and running
with at least one Doctrine entity, with data.

Installation
------------

First install Calista using composer:

.. code-block:: sh

   composer require makinacorpus/php-calista

.. note::

   This API is still in very early stage, you project must have the ``minimum-stability`` set to ``dev``.

Then edit your ``app/AppKernel.php`` PHP file and add the bundle:

.. code-block:: php

   <?php

   use Symfony\Component\HttpKernel\Kernel;
   use Symfony\Component\Config\Loader\LoaderInterface;

   class AppKernel extends Kernel
   {
       public function registerBundles()
       {
           $bundles = [
               // ...
               new \MakinaCorpus\Calista\CalistaBundle(),
           ];

           // ...

           return $bundles;
        }
    }

Enable the ``property_info`` component, edit your ``app/config/config.yml`` file
then add the ``property_info`` section:

.. code-block:: yaml

   framework:
       # ...
       property_info:
           enabled: true

Add the bundle routing configuration in ``app/config/routing.yml``:

.. code-block:: yaml

   # ...

   calista:
       resource: '@CalistaBundle/config/routing.yml'

Clear caches and check nothing is broken:

.. code-block:: sh

   bin/console cache:clear

Now let's proceed to your first datasource registration.

Write your first page (using Doctrine)
--------------------------------------

This API is backend independent, nevertheless it provides a default, striped-down
yet incomplete Doctrine datasource: we are going to use it for the sake of example.

As a pre-requisite, you must have a working Doctrine entity in your application
with data in your database, we will name this entity ``AppBundle:MyEntity`` in
this guide.

Step 1: Register the datasource
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Edit the ``app/config/services.yml`` file and add the following service:

.. code-block:: yaml

   services:
       # ...

       app.datasource.my_entity:
           public: true
           class: MakinaCorpus\Calista\Bridge\Doctrine\DoctrineDatasource
           arguments: ['AppBundle:MyEntity', '@doctrine']
           # 'id' attribute is the calista name of the datasource, a shortcut
           # you may use in various later definitions
           tags: [{name: calista.datasource, id: my_entity}]

Clear caches and check nothing is broken:

.. code-block:: sh

   bin/console cache:clear

Now let's proceed to your first page definition.

Step 2: Register the calista page
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Create the ``app/config/pages.yml``:

.. code-block:: yaml

   calista:
       pages:
           # Key here is the page name for the application
           my_first_page_with_entities:
               # This is the 'id' attribute of your service tag
               datasource: my_entity
               input:
                   # Default limit
                   limit_default: 30
                   # Enable or not pager, current Doctrine implementation is limited
                   # and does not yet enable paging
                   pager_enable: false
                   search_enable: false
                   # Change this any property on which you can sort
                   sort_default_field: someEntityProperty
                   sort_default_order: desc
               view:
                   show_filters: false
                   show_pager: false
                   show_search: false
                   show_sort: true
                   # This implementation will display an Twitter Bootstrap HTML
                   # admin page, enough for testing, a few others are provided
                   # per default
                   view_type: twig_page

Then add at the top of the ``app/config/config.yml``:

.. code-block:: yaml

   imports:
       # ...
       - { resource: pages.yml }

Clear caches and check nothing is broken:

.. code-block:: sh

   bin/console cache:clear

Now let's proceed to your route and controller definition.

Step 3: Write a controller and register a route
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Create a new ``src/AppBundle/Controller/MyEntityController.php`` file:

.. code-block:: php

   <?php

   namespace AppBundle\Controller;

   use MakinaCorpus\Calista\Controller\PageControllerTrait;
   use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
   use Symfony\Bundle\FrameworkBundle\Controller\Controller;
   use Symfony\Component\HttpFoundation\Request;

   /**
    * The controller needs to extends default Symfony's one only for the get() method.
    */
   class MyEntityController extends Controller
   {
       use PageControllerTrait;

       /**
        * @Route("/admin/my-entites", name="app_admin_my_entities")
        */
       public function adminListAction(Request $request)
       {
            return $this->renderPageResponse('my_first_page_with_entities', $request);
       }
   }

Clear caches a very last time:

.. code-block:: sh

   bin/console cache:clear

An if nothing is broken, visit your site: http://127.0.0.1:8000/admin/my-entites

At this point, you will notice that the page has no layout and no CSS, proceed
with the next step to add the Bootstrap 3 framework, that will show you the
default page styling.

Step 3: Embedding into a page layout
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Because default template uses Twitter Bootstrap 3 code, let's start with
creating a new base page template for the sake of our example, create the
``app/Resources/views/bootstrap-base.html.twig``:

.. code-block:: twig

   <!DOCTYPE html>
   <html lang="en">
   <head>
   <meta charset="utf-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <title>{{ "Calista test site"|trans }}</title>
   <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
   <!--[if lt IE 9]>
   <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
   <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
   <![endif]-->
   </head>
   <body>
     {% block body %}
     {% endblock %}
     <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
     <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
     <!-- Include all compiled plugins (below), or include individual files as needed -->
     <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
     <script src="{{ asset('bundles/calista/calista.min.js') }}"></script>
     {% block javascripts %}
     {% endblock %}
   </body>
   </html>

This is, of course, just a an example, you may use the frontend framework of
your own choice, but you'll probably need to extend and adapt the default
Calista page template.

.. note::

   Please notice the ``<script src="{{ asset('bundles/calista/calista.min.js') }}"></script>``
   line, it enables pages AJAX refresh, independently of your frontend framework
   choice you must add this JavaScript for AJAX refresh to work.

Create your first page template file ``app/Resources/views/my-entity/admin-list.html.twig``:

.. code-block:: twig

   {% extends 'bootstrap-base.html.twig' %}

   {% block body %}
       {{ calista_page('my_first_page_with_posts') }}
   {% endblock %}

Edit the ``app/config/pages.yml`` file, and change the default template to
``@calista/page/page-navbar.html.twig``:

.. code-block:: yaml

   calista:
       pages:
           my_first_page_with_entities:
               # ...
               view:
                   # ...
                   templates:
                       default: '@calista/page/page-navbar.html.twig'
                   view_type: twig_page

End with rewriting ``src/AppBundle/Controller/MyEntityController.php`` file:

.. code-block:: php

   <?php

   namespace AppBundle\Controller;

   use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
   use Symfony\Bundle\FrameworkBundle\Controller\Controller;
   use Symfony\Component\HttpFoundation\Request;

   /**
    * The controller needs to extends default Symfony's one only for the get()
    * method.
    */
   class MyEntityController extends Controller
   {
       /**
        * @Route("/admin/my-entites", name="app_admin_my_entities")
        */
       public function adminListAction (Request $request)
       {
           return $this->render('my-entity/admin-list.html.twig');
       }
   }

Clear caches a very last time:

.. code-block:: sh

   bin/console cache:clear

An if nothing is broken, visit your site: http://127.0.0.1:8000/admin/my-entites

Bonus step: add a CSV export
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Edit the ``app/config/pages.yml`` file and add the following page:

.. code-block:: yaml

   calista:
       pages:
           # ...

           my_first_csv_export:
               datasource: my_entity
               input:
                   limit_default: 1000000
               view:
                   extra:
                      add_bom: true
                      add_headers: true
                      filename: my_entities.csv
                   view_type: csv_stream

Add the CSV export action and route, edit the ``src/AppBundle/Controller/MyEntityController.php`` file:

.. code-block:: php

   <?php

   namespace AppBundle\Controller;

   use MakinaCorpus\Calista\Controller\PageControllerTrait;
   // ...

   /**
    * The controller needs to extends default Symfony's one only for the get()
    * method.
    */
   class PostController extends Controller
   {
       use PageControllerTrait;

       // ...

       /**
        * @Route("/admin/my-entites/csv", name="app_admin_my_entities_csv")
        */
       public function adminListCsvAction(Request $request)
       {
           return $this->renderPageResponse('my_first_csv_export', $request);
       }
   }

We added:

 - the ``MakinaCorpus\Calista\Controller\PageControllerTrait`` use statement,
 - the ``adminListCsvAction()`` method.

Clear caches once again:

.. code-block:: sh

   bin/console cache:clear

An if nothing is broken, visit your site: http://127.0.0.1:8000/admin/my-entites/csv

.. note::

   All filters, sort and paging capabilities defined by the datasource can be
   used via incomming GET parameters, you may write an URL such as:
   http://127.0.0.1:8000/admin/my-entites/csv?st=title&by=asc&someField=someValue

Bonus step: use Content-Type to switch template
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

   <?php

   namespace AppBundle\Controller;

   use MakinaCorpus\Calista\Controller\PageControllerTrait;
   use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
   use Symfony\Bundle\FrameworkBundle\Controller\Controller;
   use Symfony\Component\HttpFoundation\Request;
   use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

   class PostController extends Controller
   {
       use PageControllerTrait;

       /**
        * @Route("/admin/my-entites/csv", name="app_admin_my_entities_csv")
        */
       public function adminListAction(Request $request)
       {
           foreach ($request->getAcceptableContentTypes() as $contentType) {
               switch ($contentType) {

                   case 'application/xhtml+xml':
                   case 'text/html':
                       return $this->render('my-entity/admin-list.html.twig');

                   case 'text/csv':
                       return $this->renderPageResponse('my_first_csv_export', $request);
               }
           }

           throw new UnsupportedMediaTypeHttpException();
       }
   }
