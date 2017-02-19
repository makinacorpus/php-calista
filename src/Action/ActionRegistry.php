<?php

namespace MakinaCorpus\Drupal\Dashboard\Action;

final class ActionRegistry
{
    /**
     * @var ActionProviderInterface[]
     */
    private $providers = [];

    /**
     * Register providers
     *
     * @param ActionProviderInterface $provider
     */
    public function register(ActionProviderInterface $provider)
    {
        $this->providers[] = $provider;
    }

    /**
     * Get actions for item
     *
     * @param mixed $item
     * @param bool $primaryOnly
     *
     * @return Action[]
     */
    public function getActions($item, $primaryOnly = false)
    {
        $ret = [];

        foreach ($this->providers as $provider) {
            if ($provider->supports($item)) {
                $ret = array_merge($ret, $provider->getActions($item));
            }
        }

        if ($primaryOnly) {
            $ret = array_filter($ret, function (Action $action) {
                return $action->isPrimary();
            });
        }

        usort($ret, function (Action $a, Action $b) {
            $ap = $a->getPriority();
            $bp = $b->getPriority();
            if ($ap == $bp) {
                return 0;
            }
            return ($ap < $bp) ? -1 : 1;
        });

        return $ret;
    }
}
