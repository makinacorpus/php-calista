<?php

namespace MakinaCorpus\Dashboard\Drupal\Datasource\Node;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Dashboard\Datasource\AbstractDatasource;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Drupal\Datasource\QueryExtender\DrupalPager;

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
     * @return \DatabaseConnection
     */
    final protected function getDatabase()
    {
        return $this->database;
    }

    /**
     * Get Drupal database connection
     *
     * @return EntityManager
     */
    final protected function getEntityManager()
    {
        return $this->entityManager;
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
            'n.title'       => $this->t("title"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['n.changed', Query::SORT_DESC];
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
    protected function preloadDependencies(array $nodeIdList)
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
     * @param Query $query
     */
    protected function applyFilters(\SelectQuery $select, Query $query)
    {
    }

    /**
     * Returns a column on which an arbitrary sort will be added in order to
     * ensure that besides user selected sort order, it will be  predictible
     * and avoid sort glitches.
     *
     * @return string
     */
    protected function getPredictibleOrderColumn()
    {
        return 'n.nid';
    }

    /**
     * Should the implementation add group by n.nid clause or not
     *
     * It happens that some complex implementation will add their own groups,
     * case in which we should not interfer.
     *
     * @return bool
     */
    protected function addGroupby()
    {
        return true;
    }

    /**
     * Create node select query, override this to change it
     *
     * @param array $query
     *   Incoming query, might be modified for business purposes
     *
     * @return \SelectQuery
     */
    protected function createSelectQuery(Query $query)
    {
// @todo fixme
//         if (empty($query['user_id'])) {
//             // @todo fixme
//             $query['user_id'] = $GLOBALS['user']->uid;
//         }

        $select = $this->getDatabase()->select('node', 'n')->fields('n', ['nid'])->addTag('node_access');

        if ($this->addGroupby()) {
            $select->groupBy('n.nid');
        }

        return $select;
    }

    /**
     * Implementors must set the node table with 'n' as alias, and call this
     * method for the datasource to work correctly.
     *
     * @param \SelectQuery $select
     * @param Query $query
     *
     * @return \SelectQuery
     *   It can be an extended query, so use this object.
     */
    protected function process(\SelectQuery $select, Query $query)
    {
        $sortOrder = Query::SORT_DESC === $query->getSortOrder() ? 'desc' : 'asc';
        if ($query->hasSortField()) {
            $select->orderBy($query->getSortField(), $sortOrder);
        }
        $select->orderBy($this->getPredictibleOrderColumn(), $sortOrder);

        if ($searchString = $query->getRawSearchString()) {
            $select->condition('n.title', '%' . db_like($searchString) . '%', 'LIKE');
        }

        // Also add a few joins,  that might be useful later
        $select->leftJoin('history', 'h', "h.nid = n.nid AND h.uid = :history_uid", [':history_uid' => $query['user_id']]);

        $this->applyFilters($select, $query);

        return $select->extend(DrupalPager::class)->setQuery($query);
    }

    /**
     * {@inheritdoc}
     *
     * In order to validate, we don't need sort etc...
     */
    public function validateItems(Query $query, array $idList)
    {
        $select = $this->createSelectQuery($query);

        // This is mandatory, else some query conditions could attempt to use
        // table and it would fail with sql exceptions
        $select->leftJoin('history', 'h', "h.nid = n.nid AND h.uid = :history_uid", [':history_uid' => $query['user_id']]);
        $this->applyFilters($select, $query);

        // Do an except (interjection) to determine if some identifiers from
        // the input set are not in the dataset returned by the query, but SQL
        // even standard does not allow us to do that easily, hence the
        // array_diff() call after fetching the col.
        // @todo this is unperformant, comparing count result would be better
        //   but more dangerous SQL-wise (we must be absolutely sure that nid
        //   colum is deduplicated)
        $col = $select->condition('n.nid', $idList)->execute()->fetchCol();

        return array_diff($idList, $col) ? false : true;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $select = $this->createSelectQuery($query);
        $select = $this->process($select, $query);

        // Preload and set nodes at once
        return $this->preloadDependencies($select->execute()->fetchCol());
    }
}
