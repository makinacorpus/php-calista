Getting started
===============

This API can be used without the Symfony framework, but this tutorial will help
you set it up in a Symfony project.

Installation
------------

First install it using composer:

.. code-block:: sh

   cd /path/to/your/symfony/app
   composer request makinacorpus/php-calista

Then edit your ``app/AppKernel.php`` PHP file and add the bundle:

.. code-block:: php

    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        $bundles = [
            // ...
            new \MakinaCorpus\Calista\CalistaBundle(),
        ];

        // ...

        return $bundles;
    }

Clear caches and check nothing is broken:

.. code-block:: sh

   cd /path/to/your/symfony/app
   bin/console cache:clear

Now let's proceed to your first page definition.

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

       app.datasource.my_entity:
           public: true
           class: MakinaCorpus\Calista\Bridge\Doctrine\DoctrineDatasource
           arguments: ['AppBundle:MyEntity', '@doctrine']
           tags: [{name: calista.datasource, id: my_entity}]

Step 2: Register the calista page
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Create a new ``pages.yml`` file into your bundle ``src/AppBundle/Resources/config``
folder containing:

.. code-block:: yaml

   calista:
       pages:
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
                   # admin page, enough for testing
                   view_type: twig_page

Step 3: Register the page configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Please note that in a ideal world, you could copy/paste the ``pages.yml`` content
into your existing ``services.yml`` file, with this structure:

.. code-block:: yaml

   calista:
       pages:
           # ...
   services:
       app.datasource.my_entity:
           # ...

But as of now, a bug still exist where the CalistaBundle configuration is not
processed in the right order, and the ``calista`` configuration top-level element
is not defined when the bundle extensions are loaded, which makes Symfony throw
exception on container compile phase, this will solved soon, I hope, until then
you need to use the `PrependExtensionInterface` onto your extension. Edit your
``src/AppBundle/DependencyInjection/AppBundleExtension.php`` file:

.. code-block:: php

   <?php

   namespace AppBundle\DependencyInjection;

   use Symfony\Component\Config\FileLocator;
   use Symfony\Component\DependencyInjection\ContainerBuilder;
   use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
   use Symfony\Component\DependencyInjection\Loader;
   use Symfony\Component\HttpKernel\DependencyInjection\Extension;

   class AppBundleExtension extends Extension implements PrependExtensionInterface
   {
       /**
        * {@inheritdoc}
        */
       public function load(array $configs, ContainerBuilder $container)
       {
           $loader = new Loader\YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
           $loader->load('services.yml');
       }

       /**
        * Using prepend here to ensure that Calista finds out our own configuration
        * when processing the pages.
        *
        * {@inheritdoc}
        */
       public function prepend(ContainerBuilder $container)
       {
           $loader = new Loader\YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
           $loader->load('pages.yml');
       }
   }

Step 4: Write a controller
^^^^^^^^^^^^^^^^^^^^^^^^^^

Create a new ``src/AppBundle/Controller/MyEntityController.php`` file:

.. code-block:: php

   <?php

   namespace AppBundle\Controller;

   use MakinaCorpus\Calista\Controller\PageControllerTrait;
   use Symfony\Component\HttpFoundation\Request;

   /**
    * The controller needs to extends default Symfony's one only for the get()
    * method.
    */
   class MyEntityController extends Controller
   {
       use PageControllerTrait;

       /**
        * Display an HTML entity list administration screen
        */
       public function adminListAction(Request $request)
       {
            return $this->renderPageResponse('my_first_page_with_entities', $request);
       }
   }

Step 5: Declare your route
^^^^^^^^^^^^^^^^^^^^^^^^^^


Step 6: Go there, and enjoy
^^^^^^^^^^^^^^^^^^^^^^^^^^^


Bonnus step: add a CSV export
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^



