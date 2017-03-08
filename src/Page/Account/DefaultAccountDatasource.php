<?php

namespace MakinaCorpus\Drupal\Dashboard\Page\Account;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Drupal\Dashboard\Page\PageState;
use MakinaCorpus\Drupal\Dashboard\Page\QueryExtender\DrupalPager;
use MakinaCorpus\Drupal\Dashboard\Page\SortManager;

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
     * Implementors should override this method to add their filters
     *
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        // @todo build commong database filters for account datasource
        return [];
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
        return ['u.created', SortManager::DESC];
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
        return $this->entityManager->getStorage('user')
                                   ->loadMultiple($accountIdList)
        ;
    }

    /**
     * Implementors should override this method to apply their filters
     *
     * @param \SelectQueryInterface $select
     * @param mixed[] $query
     * @param PageState $pageState
     */
    protected function applyFilters(\SelectQueryInterface $select, $query, PageState $pageState)
    {
    }

    /**
     * Implementors must set the users table with 'u' as alias, and call this
     * method for the datasource to work correctly.
     *
     * @param \SelectQueryInterface $select
     * @param mixed[] $query
     * @param PageState $pageState
     *
     * @return \SelectQuery
     *   It can be an extended query, so use this object.
     */
    final protected function process(\SelectQueryInterface $select, $query, PageState $pageState)
    {
        if ($pageState->hasSortField()) {
            $select->orderBy(
              $pageState->getSortField(),
              SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc'
            );
        }
        $select->orderBy(
          'u.uid',
          SortManager::DESC === $pageState->getSortOrder() ? 'desc' : 'asc'
        );

        $sParam = $pageState->getSearchParameter();
        if (!empty($query[$sParam])) {
            $select->condition(
              'u.name',
              '%'.db_like($query[$sParam]).'%',
              'LIKE'
            );
        }

        $this->applyFilters($select, $query, $pageState);

        return $select
          ->extend(DrupalPager::class)
          ->setPageState($pageState)
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function hasSearchForm()
    {
        return true;
    }

    /**
     * Get items to display
     *
     * @param mixed[] $query
     * @param \MakinaCorpus\Drupal\Dashboard\Page\PageState $pageState
     * @return \Drupal\Core\Entity\EntityInterface[]
     */
    public function getItems($query, PageState $pageState)
    {
        $select = $this->getDatabase()->select('users', 'u');
        $select = $this->process($select, $query, $pageState);

        // Remove anonymous user
        $accountIdList = $select
          ->fields('u', ['uid'])
          ->condition('u.uid', 0, '>')
          ->groupBy('u.uid')
          ->execute()
          ->fetchCol()
        ;

        // Preload and set nodes at once
        return $this->preloadDependencies($accountIdList);
    }
}
