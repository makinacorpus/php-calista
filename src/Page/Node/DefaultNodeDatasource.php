<?php

namespace MakinaCorpus\Drupal\Dashboard\Page\Node;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;

/**
 * Base implementation for node admin datasource, that should fit most use cases.
 */
class DefaultNodeDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $entityManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param EntityManager $entityManager
     */
    public function __construct(\DatabaseConnection $database, EntityManager $entityManager)
    {
        $this->database = $database;
        $this->entityManager = $entityManager;
    }

    /**
     * Get Drupal database connection
     *
     * @return \\DatabaseConnection
     */
    final protected function getDatabase()
    {
        return $this->database;
    }

    /**
     * Implementors should override this method to add their filters
     *
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        // @todo build commong database filters for node datasource into some
        //   trait or abstract implemetnation to avoid duplicates
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'n.created'     => $this->t("creation date"),
            'n.changed'     => $this->t("lastest update date"),
            'h.timestamp'   => $this->t('most recently viewed'),
            'n.status'      => $this->t("status"),
            'n.uid'         => $this->t("owner"),
            'n.title.title' => $this->t("title"),
            'n.is_flagged'  => $this->t("flag"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['n.changed', SortManager::DESC];
    }

    /**
     * Preload pretty much everything to make admin listing faster
     *
     * You should call this.
     *
     * @param int[] $nodeIdList
     *
     * @return NodeInterface[]
     *   The loaded nodes
     */
    final protected function preloadDependencies(array $nodeIdList)
    {
        $userIdList = [];
        $nodeList = $this->entityManager->getStorage('node')->loadMultiple($nodeIdList);

        foreach ($nodeList as $node) {
            $userIdList[$node->uid] = $node->uid;
        }

        if ($userIdList) {
            $this->entityManager->getStorage('user')->loadMultiple($userIdList);
        }

        return $nodeList;
    }

    /**
     * Implementors should override this method to apply their filters
     *
     * @param \SelectQuery $select
     * @param mixed[] $query
     * @param PageState $pageState
     */
    protected function applyFilters(\SelectQuery $select, $query, PageState $pageState)
    {
    }

    /**
     * Implementors must set the node table with 'n' as alias, and call this
     * method for the datasource to work correctly.
     *
     * @param \SelectQuery $select
     * @param mixed[] $query
     * @param PageState $pageState
     *
     * @return \SelectQuery
     *   It can be an extended query, so use this object.
     */
    final protected function process(\SelectQuery $select, $query, PageState $pageState)
    {
        if ($pageState->hasSortField()) {
            $select->orderBy($pageState->getSortField(), SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');
        }
        $select->orderBy('n.nid', SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc');

        $sParam = $pageState->getSearchParameter();
        if (!empty($query[$sParam])) {
            $select->condition('n.title', '%' . db_like($query[$sParam]) . '%', 'LIKE');
        }

        // Also add a few joins,  that might be useful later
        $select->leftJoin('history', 'h', "h.nid = n.nid AND h.uid = :history_uid", [':history_uid' => $query['user_id']]);

        $this->applyFilters($select, $query, $pageState);

        return $select
            ->addTag('node_access')
            //->groupBy('n.nid')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
        ;
    }

    /**
     * {@inheritdoc}
     *
     * Override this method to change fitlers
     */
    public function getItems($query, PageState $pageState)
    {
        if (empty($query['user_id'])) {
            // @todo fixme
            $query['user_id'] = $GLOBALS['user']->uid;
        }

        $select = $this->getDatabase()->select('node', 'n');
        $select = $this->process($select, $query, $pageState);

        // JOIN with {history} is actually done in the parent implementation
        $nodeIdList = $select
            ->fields('n', ['nid'])
            ->groupBy('n.nid')
            ->execute()
            ->fetchCol()
        ;

        // Preload and set nodes at once
        return $this->preloadDependencies($nodeIdList);
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFormParamName()
    {
        return 's';
    }
}
