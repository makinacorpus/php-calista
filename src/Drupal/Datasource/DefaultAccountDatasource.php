<?php

namespace MakinaCorpus\Dashboard\Drupal\Datasource;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Dashboard\Datasource\AbstractDatasource;
use MakinaCorpus\Dashboard\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Dashboard\Datasource\Filter;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Drupal\Datasource\QueryExtender\DrupalPager;
use Drupal\user\User;

/**
 * Default data source for accounts
 */
class DefaultAccountDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    protected $database;
    protected $entityManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     * @param EntityManager $entityManager
     */
    public function __construct(\DatabaseConnection $database, EntityManager $entityManager)
    {
        $this->database = $database;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return User::class;
    }

    /**
     * Implementors should override this method to add their filters
     *
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $roles = user_roles(true);
        unset($roles[DRUPAL_AUTHENTICATED_RID]);

        return [
            (new Filter('status', $this->t("Active")))->setChoicesMap(
                [
                    0 => $this->t("No"),
                    1 => $this->t("Yes"),
                ]
            ),
            (new Filter('role', $this->t("Role")))->setChoicesMap($roles),
            (new Filter('name', $this->t("Name"))),
        ];
    }

    /**
     * Implementors should override this method to add their sorts
     *
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
          'u.created' => $this->t("creation date"),
          'u.access'  => $this->t("most recently access"),
          'u.login'   => $this->t("latest login date"),
          'u.status'  => $this->t("status"),
          'u.name'    => $this->t("name"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['u.created', Query::SORT_DESC];
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
     * Preload pretty much everything to make admin listing faster
     *
     * You should call this.
     *
     * @param int[] $accountIdList
     *
     * @return \Drupal\Core\Entity\EntityInterface[]
     *   The loaded users
     */
    final protected function preloadDependencies(array $accountIdList)
    {
        return $this->entityManager->getStorage('user')->loadMultiple($accountIdList);
    }

    /**
     * Implementors should override this method to apply their filters
     *
     * @param \SelectQueryInterface $select
     * @param Query $query
     */
    protected function applyFilters(\SelectQueryInterface $select, Query $query)
    {
        if ($query->has('name')) {
            $select->condition('name', '%'.db_like($query->get('name')).'%', 'LIKE');
        }

        if ($query->has('roles')) {
            $select->leftJoin(
                'users_roles',
                'ur',
                'ur.uid = u.uid'
            );
            $select->condition('ur.rid', $query->get('roles'));
        }
        if ($query->has('status')) {
            $select->condition('u.status', $query->get('status'));
        }
    }

    /**
     * Implementors must set the users table with 'u' as alias, and call this
     * method for the datasource to work correctly.
     *
     * @param \SelectQueryInterface $select
     * @param Query $query
     *
     * @return \SelectQuery
     *   It can be an extended query, so use this object.
     */
    final protected function process(\SelectQueryInterface $select, Query $query)
    {
        if ($query->hasSortField()) {
            $select->orderBy(
                $query->getSortField(),
                Query::SORT_DESC === $query->getSortOrder() ? 'desc' : 'asc'
            );
        }
        $select->orderBy(
            'u.uid',
            Query::SORT_DESC === $query->getSortOrder() ? 'desc' : 'asc'
        );

        if ($searchstring = $query->getSearchString()) {
            $select->condition(
                'u.name',
                '%'.db_like($searchstring).'%',
                'LIKE'
            );
        }

        $this->applyFilters($select, $query);

        return $select /*->extend(DrupalPager::class)->setQuery($query) */;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $select = $this->getDatabase()->select('users', 'u');
        $select = $this->process($select, $query);

        /** @var \MakinaCorpus\Dashboard\Drupal\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $select->extend(DrupalPager::class);
        $pager->setDatasourceQuery($query);

        // Remove anonymous user
        $accountIdList = $pager
            ->fields('u', ['uid'])
            ->condition('u.uid', 0, '>')
            ->groupBy('u.uid')
            ->execute()
            ->fetchCol()
        ;

        // Preload and set nodes at once
        $result = new DefaultDatasourceResult(User::class, $this->preloadDependencies($accountIdList));
        $result->setTotalItemCount($pager->getTotalCount());

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
}
