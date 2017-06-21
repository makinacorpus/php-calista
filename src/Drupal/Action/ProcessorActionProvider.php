<?php

namespace MakinaCorpus\Dashboard\Drupal\Action;

use MakinaCorpus\Dashboard\Action\ActionProviderInterface;

/**
 * Works with one or many processors that all supports the same type of items
 * and return actions from them
 */
class ProcessorActionProvider implements ActionProviderInterface
{
    /**
     * @var AbstractActionProcessor[]
     */
    private $processors = [];

    /**
     * Register processor instance
     *
     * @param AbstractActionProcessor $processors
     */
    public function register(AbstractActionProcessor $processor)
    {
        $this->processors[$processor->getId()] = $processor;
    }

    /**
     * Get processor instance
     *
     * @return AbstractActionProcessor
     */
    public function get($id)
    {
        if (!isset($this->processors[$id])) {
            throw new \InvalidArgumentException(sprintf("processor with id '%s' does not exist", $id));
        }

        return $this->processors[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        foreach ($this->processors as $processor) {
            if ($processor->appliesTo($item)) {
                $ret[$processor->getId()] = $processor->getAction($item, $primaryOnly, $groups);
            }
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return true;
    }
}
