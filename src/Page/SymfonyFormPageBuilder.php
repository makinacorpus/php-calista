<?php


namespace MakinaCorpus\Drupal\Dashboard\Page;


use Symfony\Component\Form\AbstractType;
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
     * @var \mixed
     */
    private $dataItems;

    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    private $formset;

    /**
     * Enable the confirm form
     *
     * @return self
     */
    public function enableConfirmForm()
    {
        $formFactory = Forms::createFormFactoryBuilder()
                            ->addExtension(new HttpFoundationExtension())
                            ->addExtension(new ValidatorExtension(Validation::createValidator()))
                            ->getFormFactory()
        ;
        $formBuilder = $formFactory
            ->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', null, [
                'allow_extra_fields' => true,  // This is required to handle the second request with extra data
            ])
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $class
     *   Form parameter name.
     * @param callable $callback
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createFormset(
        Request $request, $class = 'MakinaCorpus\Drupal\Dashboard\Form\Type\SelectionFormType', $callback = null
    ) {
        if (!in_array(AbstractType::class, class_parents($class))) {
            throw new \InvalidArgumentException(sprintf('class %s is not a child of AbstractType', $class));
        }

        $this->dataItems = $this->getDataItems($request);

        if ($callback) {
            if (!is_callable($callback)) {
                throw new \InvalidArgumentException(sprintf('%s is not a callback', $class));
            }
            $defaultValues = call_user_func($callback, $this->dataItems);
        } else {
            $defaultValues = [];
            foreach ($this->dataItems as $item) {
                $defaultValues[$item->id()] = [
                    'id' => $item->id(),
                ];
            }
        }

        $formFactory = Forms::createFormFactoryBuilder()
                            ->addExtension(new HttpFoundationExtension())
                            ->addExtension(new ValidatorExtension(Validation::createValidator()))
                            ->getFormFactory()
        ;
        $formBuilder = $formFactory
            ->createBuilder()
            ->add('forms', CollectionType::class, [
                'entry_type'  => $class,
                'constraints' => [
                    new Callback([$this, 'hasSelectedValue']),
                ],
            ])
            ->add('csrf_token', HiddenType::class, [
                'data'        => drupal_get_token(),
                'constraints' => [
                    new Callback([$this, 'isValidToken']),
                ],
            ])
            ->setData([
                'forms' => $defaultValues,
            ])
        ;

        $this->formset = $formBuilder->getForm();
        $this->formset->handleRequest($request);


        // Test if the form has been submitted
        $this->storedData = $request->getSession()->get($this->computeId());

        // This is actually a form we are going to process manually
        if ($this->formset->isSubmitted() && $this->formset->isValid() && !$this->storedData) {
            // Store data
            $data = $this->formset->getData();
            $request->getSession()->set($this->computeId(), $data);
            $this->storedData = $data;

        }

        if ($this->confirmForm) {
            $this->confirmForm->handleRequest($request);
        }

        return $this->formset;
    }

    /**
     * Return true if this form page needs confirmation.
     *
     * @return bool
     */
    public function needsConfirmation()
    {
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
     * Check drupal token
     *
     * @param $value
     * @param \Symfony\Component\Validator\Context\ExecutionContextInterface $context
     */
    public function hasSelectedValue($value, ExecutionContextInterface $context)
    {
        if (!array_filter($value, function ($d) {return !empty($d['selected']);})) {
            $context->buildViolation('At least an element has to be selected.')
                    ->addViolation()
            ;
        }
    }


    /**
     * Get data items
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \mixed[]
     */
    public function getDataItems(Request $request)
    {
        return $this->search($request)->getItems();
    }

    /**
     * Indicate if the formset is ready to process
     *
     * @return bool
     */
    public function isReadyToProcess()
    {
        // In order to be ready, form must be confirmed and has data
        if ($this->confirmForm && !$this->confirmForm->isSubmitted()) {
            return false;
        }

        return (bool)$this->getStoredData();
    }

    /**
     *
     *
     * @return mixed
     */
    public function getStoredData()
    {
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
}
