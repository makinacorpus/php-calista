Getting started
===============

This API can be used without the Symfony framework, but this tutorial will help
you set it up in a Symfony project.

Installation
------------

First install it using composer:

.. code-block:: sh

   cd /path/to/your/symfony/app
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

Enable the ``property_info`` component. Edit your ``app/config/config.yml`` file
then add ``property_info: {enabled: true}`` in the ``framework`` section:

.. code-block:: yaml

   framework:
       # ...
       property_info:
           enabled: true

Clear caches and check nothing is broken:

.. code-block:: sh

   bin/console cache:clear

Now let's proceed to your first datasource registration.

Write your first page (using Doctrine)
--------------------------------------

This API is backend, data source independent, nevertheless is provides a
default, striped down and rather incomplete Doctrine datasource you can re-use
that will be sufficient for the sake of example.

As a pre-requisite, you must have a working Doctrine entity in your application
that will reference as ``AppBundle:MyEntity``.

Step 1: Register the datasource
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Edit your `app/config/services.yml` and add the following service:

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
                   limit_default: 50
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
       <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
       <title>My site</title>

       <!-- Bootstrap -->
       <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

       <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
       <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
       <!--[if lt IE 9]>
         <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
         <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
       <![endif]-->
     </head>
     <body>
       {% block container %}
         <div class="container-fluid">
           <div class="row">
             <div class="col-md-12">
               {% block body %}{% endblock %}
             </div>
           </div>
         </div>
       {% endblock %}

       <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
       <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
       <!-- Include all compiled plugins (below), or include individual files as needed -->
       <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
       {% block javascripts %}{% endblock %}
     </body>
   </html>

This is, of course, just a an example.

And now, create your own controller template file ``app/Resources/views/my-entity/admin-list.html.twig``:

.. code-block:: twig

   {% extends 'bootstrap-base.html.twig' %}

   {% block body %}
       <h1>Posts</h1>

       {{ calista_page('my_first_page_with_posts') }}
   {% endblock %}

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

Bonnus step: add a CSV export
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^



