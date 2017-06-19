<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Dashboard\Action\Action;

/**
 * Uses an array as datasource
 */
class IntActionProvider implements ActionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /** @var \MakinaCorpus\Dashboard\Tests\Mock\IntItem $item */
        if ($item->id < 0) {
            $ret[] = new Action('Make it positive', '/make/it/positive', ['value' => $item->id], 'plus');
        } else {
            $ret[] = new Action('Make it negative', '/make/it/negative', ['value' => $item->id], 'minus');
        }

        if (!$primaryOnly) {
            $ret[] = new Action('Trash it', '/trash/it', ['value' => $item->id], 'trash', 0, false);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof IntItem;
    }
}
