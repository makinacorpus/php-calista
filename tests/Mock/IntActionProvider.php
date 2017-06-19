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
            $ret[] = new Action('Make it positive', '/make/it/positive', ['int' => $item], 'plus');
        } else {
            $ret[] = new Action('Make it negative', '/make/it/negative', ['int' => $item], 'minus');
        }

        if (!$primaryOnly) {
            $ret[] = new Action('Trash it', '/trash/it', ['int' => $item], 'trash');
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
