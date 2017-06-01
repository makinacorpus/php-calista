<?php

namespace MakinaCorpus\Drupal\Dashboard\Page;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validation;

class SymfonyFormPageBuilder extends PageBuilder
{
    /**
     * @var \Symfony\Component\Form\Form
     */
    private $confirmForm;

    /**
     * @var \mixed
     */
    private $storedData;

    /**
     * @var bool
     */
    private $requestHandled = false;

    /**
     * @var \Symfony\Component\Form\Form
     */
    private $formset;

    /**
     * Enable the confirm form
     *
     * @return $this
     */
    public function enableConfirmForm()
    {
        $formFactory = Forms::createFormFactoryBuilder()
                            ->addExtension(new HttpFoundationExtension())
                            ->addExtension(new ValidatorExtension(Validation::createValidator()))
                            ->getFormFactory()
        ;
        $formBuilder = $formFactory
            ->createNamedBuilder('confirm')
            ->add('confirm', HiddenType::class, [
                'data' => true,
            ])
            ->add('csrf_token', HiddenType::class, [
                'data'        => drupal_get_token(),
                'constraints' => [
                    new Callback([$this, 'isValidToken']),
                ],
            ])
        ;

        $this->confirmForm = $formBuilder->getForm();

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
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createFormset($class = 'MakinaCorpus\Drupal\Dashboard\Form\Type\SelectionFormType')
    {
        if (!in_array(AbstractType::class, class_parents($class))) {
            throw new \InvalidArgumentException(sprintf('class %s is not a child of AbstractType', $class));
        }

        $formFactory = Forms::createFormFactoryBuilder()
                            ->addExtension(new HttpFoundationExtension())
                            ->addExtension(new ValidatorExtension(Validation::createValidator()))
                            ->getFormFactory()
        ;

        $this->formset = $formFactory
            ->createNamedBuilder('formset')
            ->add('forms', CollectionType::class, [
                'entry_type' => $class,
                // TODO: find a way to make configurable (we dont always want a selection)
                // implement validation groups with submits?
                // @see http://symfony.com/doc/current/form/button_based_validation.html
                //'constraints' => [
                //    new Callback([$this, 'hasSelectedValue']),
                //],
            ])
            ->add('csrf_token', HiddenType::class, [
                'data'        => drupal_get_token(),
                'constraints' => [
                    new Callback([$this, 'isValidToken']),
                ],
            ])
            ->getForm()
        ;

        return $this->formset;
    }

    /**
     * Return true if this form page needs confirmation.
     *
     * @return bool
     */
    public function needsConfirmation()
    {
        if (!$this->requestHandled) {
            throw new LogicException("The request has not been handled by this PageFormBuilder yet.");
        }

        // A form needs confirmation if it has confirmForm...
        if ($this->confirmForm) {
            // If the form has been submitted and is valid
            return $this->formset->isSubmitted() && $this->formset->isValid();
        }

        return false;
    }

    /**
     * Check drupal token
     *
     * @param $token
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function isValidToken($token, ExecutionContextInterface $context)
    {
        if (!drupal_valid_token($token)) {
            $context->buildViolation('Wrong token')
                    ->addViolation()
            ;
        }
    }

    /**
     * Check that form has selected values
     *
     * @param $value
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function hasSelectedValue($value, ExecutionContextInterface $context)
    {
        $selectedElements = array_filter($value, function ($d) {
            return !empty($d['selected']);
        });

        if (!$selectedElements) {
            $context->buildViolation('At least an element has to be selected.')
                    ->addViolation()
            ;
        }
    }

    /**
     * Indicate if the formset is ready to process
     *
     * @return bool
     */
    public function isReadyToProcess()
    {
        if (!$this->requestHandled) {
            throw new LogicException("The request has not been handled by this PageFormBuilder yet.");
        }

        // In order to be ready, form must be confirmed and has data
        if ($this->confirmForm && !$this->confirmForm->isSubmitted()) {
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
                throw new \InvalidArgumentException("No data named ".$name);
            }

            return $this->storedData[$name];
        }

        return $this->storedData;
    }

    /**
     * Get the loaded items from selection
     *
     * @param null $data
     * @return array
     */
    public function getSelectedItems($data = null)
    {
        if (!$data) {
            $data = $this->formset->getData();
        }
        $selectedIds = array_keys(array_filter($data['forms'], function ($d) {
            return !empty($d['selected']);
        }));

        return array_intersect_key($this->dataItems, array_flip($selectedIds));
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
        $request->getSession()->remove($this->computeId());
    }

    /**
     * {@inheritDoc}
     *
     * Also include the formset for display
     */
    public function createPageView(PageResult $result, array $arguments = [])
    {
        $arguments['formset'] = $this->formset->createView();

        return parent::createPageView($result, $arguments);
    }

    /**
     * Make the formset anr confirm form handle request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param callable $callback
     */
    public function handleRequest(Request $request, $callback = null)
    {
        // Get items to work on
        $dataItems = $this->search($request)->getItems();

        //
        if ($callback) {
            if (!is_callable($callback)) {
                throw new \InvalidArgumentException(sprintf('%s is not a callback', $callback));
            }
            $defaultValues = call_user_func($callback, $dataItems);
        } else {
            $defaultValues = [];
            foreach ($dataItems as $item) {
                $defaultValues[$item->id()] = [
                    'id' => $item->id(),
                ];
            }
        }

        $this->formset->setData(['forms' => $defaultValues])
                      ->handleRequest($request)
        ;
        $data = $this->formset->getData();

        if ($this->confirmForm) {
            $this->confirmForm->handleRequest($request);

            // Test if the formset has been submitted and store data if we need a confirmation form
            if ($this->formset->isSubmitted() && $this->formset->isValid()) {
                $data['clicked_button'] = $this->formset->getClickedButton()->getName();
                $request->getSession()->set($this->computeId(), $data);
                $this->storedData = $data;
            } else {
                $this->storedData = $request->getSession()->get($this->computeId());
            }
        } else {
            // Else if no confirm form there's no need to use the session
            $this->storedData = $data;
        }

        $this->requestHandled = true;
    }
}
