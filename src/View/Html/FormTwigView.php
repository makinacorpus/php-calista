<?php

namespace MakinaCorpus\Calista\View\Html;

use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Calista\Error\CalistaError;
use MakinaCorpus\Calista\Form\Type\SelectionFormType;
use MakinaCorpus\Calista\View\ViewDefinition;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Extends the page builder to add an embedded form feature, that allows items
 * selection and usage in a more global Symfony form.
 */
class FormTwigView extends TwigView
{
    /**
     * @var FormInterface
     */
    private $confirmForm;

    /**
     * @var mixed
     */
    private $storedData;

    /**
     * @var bool
     */
    private $requestHandled = false;

    /**
     * @var bool
     */
    private $confirmationCancelled = false;

    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var string
     */
    private $formItemClass = SelectionFormType::class;

    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param EventDispatcherInterface $dispatcher
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(\Twig_Environment $twig, EventDispatcherInterface $dispatcher, FormFactoryInterface $formFactory)
    {
        parent::__construct($twig, $dispatcher);

        $this->formFactory = $formFactory;
    }

    /**
     * Builds and enables the confirm form
     *
     * @return $this
     */
    public function enableConfirmForm()
    {
        $formBuilder = $this->formFactory
            ->createNamedBuilder('confirm')
            ->add('confirm', SubmitType::class, ['label' => 'Confirm'])
            ->add('cancel', SubmitType::class, ['label' => 'Cancel'])
        ;

        $this->confirmForm = $formBuilder->getForm();

        return $this;
    }

    /**
     * Set form item class for each datasource item
     *
     * @param string $class
     *
     * @return $this
     */
    public function setFormItemClass($class)
    {
        if (!is_subclass_of($class, AbstractType::class)) {
            throw new \InvalidArgumentException(sprintf("Class %s doesn't extend %s.", $class, AbstractType::class));
        }

        $this->formItemClass = $class;

        return $this;
    }

    /**
     * If the page is to be inserted as a form widget, set the element name
     *
     * Please notice that in all cases, only the template can materialize the
     * form element, this API is agnostic from any kind of form API and cannot
     * do it automatically.
     *
     * This parameter will only be carried along to the twig template under
     * the 'form_name' variable. It is YOUR job to create the associated
     * inputs in the final template.
     *
     * @param string $class
     *   Form parameter name.
     *
     * @return FormInterface
     */
    public function getForm()
    {
        if ($this->form) {
            return $this->form;
        }

        return $this->form = $this
            ->formFactory
            ->createNamedBuilder('form')
            ->add('items', CollectionType::class, [
                'entry_type' => $this->formItemClass,
                'label'      => false,
                // TODO: find a way to make configurable (we dont always want a selection)
                // implement validation groups with submits?
                // @see http://symfony.com/doc/current/form/button_based_validation.html
                //'constraints' => [
                //    new Callback([$this, 'hasSelectedValue']),
                //],
            ])
            ->getForm()
        ;
    }

    /**
     * Returns true if this form page needs confirmation.
     *
     * @return bool
     */
    public function needsConfirmation()
    {
        if (!$this->requestHandled) {
            throw new LogicException("The request has not been handled by this PageFormBuilder yet.");
        }

        // A form needs confirmation if it has confirmForm (not cancelled)...
        if ($this->confirmForm && !$this->confirmationCancelled) {
            // If the form has been submitted and is valid
            return $this->form->isSubmitted() && $this->form->isValid();
        }

        return false;
    }

    /**
     * Check that form has selected values
     *
     * @param $value
     * @param ExecutionContextInterface $context
     */
    public function hasSelectedValue($value, ExecutionContextInterface $context)
    {
        $selectedElements = array_filter($value, function ($d) {
            return !empty($d['selected']);
        });

        if (!$selectedElements) {
            $context->buildViolation("No items selected.")->addViolation();
        }
    }

    /**
     * Indicate if the form is ready to process
     *
     * @return bool
     */
    public function isReadyToProcess()
    {
        if (!$this->requestHandled) {
            throw new LogicException("The request has not been handled by this PageFormBuilder yet.");
        }

        // In order to be ready, form must be confirmed and has data
        if ($this->confirmForm && (!$this->confirmForm->isSubmitted() || $this->confirmationCancelled)) {
            return false;
        }

        return (bool)$this->getStoredData();
    }

    /**
     * Get data
     *
     * @param string $name
     * @return mixed
     */
    public function getStoredData($name = null)
    {
        if ($name) {
            if (!isset($this->storedData[$name])) {
                if ('clicked_button' === $name) {
                    return null;
                }

                throw new \InvalidArgumentException(sprintf("No data named '%s'.", $name));
            }

            return $this->storedData[$name];
        }

        return $this->storedData;
    }

    /**
     * Get the loaded items from selection
     *
     * @return array
     */
    public function getSelectedItems()
    {
        try {
            $data = $this->getStoredData('items');
        } catch (\InvalidArgumentException $e) {
            return [];
        }

        return array_filter(
            $data,
            function ($d) {
                return !empty($d['selected']);
            }
        );
    }

    /**
     * Get the confirm form if existent
     *
     * @return \Symfony\Component\Form\Form
     */
    public function getConfirmForm()
    {
        return $this->confirmForm;
    }

    /**
     * Clear the session data
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function clearData(Request $request)
    {
        $request->getSession()->remove($this->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function createRenderer(ViewDefinition $viewDefinition, DatasourceResultInterface $items, Query $query, array $arguments = [])
    {
        $arguments['form'] = $this->getForm()->createView();

        return parent::createRenderer($viewDefinition, $items, $query, $arguments);
    }

    /**
     * Get item identifier
     *
     * @param DatasourceResultInterface $items
     * @param callable $callback
     *   A callback accepting an item array as argument and that return an
     *   ordered array of identifier list (order should be the same as the
     *   input item array); Please note that the input argument is a valid
     *   DatasourceResultInterface instance
     *
     * @return string[]
     */
    private function getItemIdentifierList(DatasourceResultInterface $items, callable $callback = null)
    {
        $ret = [];

        if ($callback) {
            $ret = call_user_func($callback, $items);
        } else {
            foreach ($items as $item) {
                if (is_scalar($item)) {
                    $id = $item;
                } else if (is_object($item)) {
                    if (method_exists($item, 'getId')) {
                        $id = $item->getId();
                    } else if (method_exists($item, 'id')) {
                        $id = $item->id();
                    } else if (property_exists($item, 'id')) {
                        $id  = $item->id;
                    } else {
                        throw new CalistaError("could not determine identifier for item");
                    }
                } else {
                    throw new CalistaError("could not determine identifier for item");
                }
                $ret[$id] = ['id' => $id];
            }
        }

        return $ret;
    }

    /**
     * Make the form and confirm form handle request
     *
     * @param Request $request
     *   Incomming request
     * @param DatasourceResultInterface $items
     *   Current datasource result
     * @param callable $callback
     *   A callback accepting an item array as argument and that return an
     *   ordered array of identifier list (order should be the same as the
     *   input item array); Please note that the input argument is a valid
     *   DatasourceResultInterface instance
     */
    public function handleRequest(Request $request, DatasourceResultInterface $items, callable $callback = null)
    {
        // Get items to work on
        $form = $this->getForm();

        // Bind initial data
        $form->setData(['items' => $this->getItemIdentifierList($items, $callback)])->handleRequest($request);

        if ($this->confirmForm) {
            $this->confirmForm->handleRequest($request);

            if (
                $this->confirmForm->isSubmitted() &&
                $this->confirmForm->getClickedButton()->getName() == 'cancel'
            ) {
                // Confirm form has been cancelled, set the data back and display form
                $this->confirmationCancelled = true;
                $data = $request->getSession()->get($this->getId());
                $this->form->setData($data);

            } else if ($this->form->isSubmitted() && $this->form->isValid()) {
                // Form has been submitted, store data into session and display confirm form.
                $data = $this->form->getData();

                // Form could have been posted by a custom button in a template, case in which
                // it will not be available within the form; moreoever, POST'ing data without
                // using a button is valid too.
                $clickedButton = $this->form->getClickedButton();
                if ($clickedButton) {
                    $data['clicked_button'] = $clickedButton->getName();
                }

                $request->getSession()->set($this->getId(), $data);
                $this->storedData = $data;

            } else {
                // Confirm form is valid, do not attempt to fetch data from original form.
                $this->storedData = $request->getSession()->get($this->getId());
            }
        } else if ($this->form->isSubmitted() && $this->form->isValid()) {

            // Form could have been posted by a custom button in a template, case in which
            // it will not be available within the form; moreoever, POST'ing data without
            // using a button is valid too.
            $clickedButton = $this->form->getClickedButton();
            if ($clickedButton) {
                $data['clicked_button'] = $clickedButton->getName();
            }

            // Else if no confirm form there's no need to use the session
            $this->storedData = $this->form->getData();
        } else {
            $this->storedData = [];
        }

        $this->requestHandled = true;
    }
}
