<?php

namespace MakinaCorpus\Dashboard\Drupal\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Dashboard\Action\Action;
use MakinaCorpus\Dashboard\Action\ActionProviderInterface;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
class CoreNodeActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    public function getActions($item, $primaryOnly = false, array $groups = [])
    {
        $ret = [];

        /** @var \Drupal\node\NodeInterface $item */
        if (node_access('view', $item)) {
            $ret[] = Action::create([
                'title'     => $this->t("View"),
                'route'     => 'node/' . $item->id(),
                'options'   => [],
                'icon'      => 'eye-open',
                'priority'  => -100,
            ]);
        }

        if (node_access('update', $item)) {
            $ret[] = Action::create([
                'title'     => $this->t("Edit"),
                'route'     => 'node/' . $item->id() . '/edit',
                'options'   => [],
                'redirect'  => true,
                'icon'      => 'pencil',
            ]);
        }

        if (node_access('delete', $item)) {
            $ret[] = Action::create([
                'title'     => $this->t("Delete"),
                'route'     => 'node/' . $item->id() . '/delete',
                'options'   => [],
                'icon'      => 'trash',
                'redirect'  => true,
                'primary'   => false,
                'group'     => 'danger'
            ]);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeInterface;
    }
}
