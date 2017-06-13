<?php

namespace MakinaCorpus\Dashboard\Drupal\Controller;

use Drupal\Core\Form\FormBuilderInterface;

use MakinaCorpus\Dashboard\Drupal\Action\ProcessorActionProvider;
use MakinaCorpus\Dashboard\Drupal\TransactionHandler;
use MakinaCorpus\Drupal\Sf\Controller;

use Symfony\Component\HttpFoundation\Request;

class ActionProcessorController extends Controller
{
    /**
     * @return FormBuilderInterface
     */
    private function getFormBuilder()
    {
        return $this->get('form_builder');
    }

    /**
     * @return ProcessorActionProvider
     */
    private function getActionProcessorRegistry()
    {
        return $this->get('udashboard.processor_registry');
    }

    /**
     * @return TransactionHandler
     */
    private function getTransactionHandler()
    {
        return $this->get('udashboard.transaction_handler');
    }

    public function processAction(Request $request)
    {
        if (!$request->query->has('item')) {
            throw $this->createNotFoundException();
        }
        if (!$request->query->has('processor')) {
            throw $this->createNotFoundException();
        }

        try {
            $processor = $this
                ->getActionProcessorRegistry()
                ->get($request->query->get('processor'))
            ;
        } catch (\Exception $e) {
            throw $this->createNotFoundException();
        }

        $item = $processor->loadItem($request->query->get('item'));
        if (!$item) {
            throw $this->createNotFoundException();
        }
        if (!$processor->appliesTo($item)) {
            throw $this->createAccessDeniedException();
        }

        $builder = $this->getFormBuilder();

        return $this
            ->getTransactionHandler()
            ->run(function () use ($builder, $processor, $item) {
                return $builder->getForm($processor->getFormClass(), $processor, $item);
            })
        ;
    }
}
