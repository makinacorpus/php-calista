<?php

namespace MakinaCorpus\Calista\Bridge\Goat;

use Goat\Mapper\MapperInterface;
use Goat\Query\Query as GoatQuery;
use Goat\Query\SelectQuery;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Calista\Error\CalistaError;

/**
 * Integrates partially with goat mapper as a datasource
 *
 * It will not automatically give you any field for filtering or display but
 * you will be able to do it by yourself while defining your views.
 */
class GoatMapperDatasource extends AbstractDatasource
{
    private $mapper;

    /**
     * Default constructor
     */
    public function __construct(MapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return $this->mapper->getClassName();
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function supportsStreaming()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPagination()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function validateItems(Query $query, array $idList)
    {
        $primaryKeyLength = count($this->mapper->getPrimaryKeyCount());
        if (!$primaryKeyLength || 1 < $primaryKeyLength) {
            throw new CalistaError("goat mapper datasource cannot validate entities with no primary key or with multiple columns primary key");
        }

        $idList = array_unique($idList);

        $select = $this->createQuery();
        $this->applyFilters($query, $select);

        $primaryKeyAlias = $this->mapper->getRelation()->getAlias().'.'.reset($this->mapper->getPrimaryKey());
        $databaseIdList = $select
            ->column($primaryKeyAlias)
            ->condition($primaryKeyAlias, $idList)
            ->execute()
            ->fetchColumn()
        ;

        return array_diff($idList, $databaseIdList) ? false : true;
    }

    /**
     * Create a goat query
     */
    private function createQuery() : SelectQuery
    {
        return $this->mapper->createSelect();
    }

    /**
     * Get mapper
     */
    final protected function getMapper() : MapperInterface
    {
        return $this->mapper;
    }

    /**
     * Apply pagination and ordering
     */
    protected function processQuery(Query $query, SelectQuery $select)
    {
        if ($query->hasSortField()) {
            $select->orderBy($query->getSortField(), Query::SORT_DESC === $query->getSortOrder() ? GoatQuery::ORDER_DESC : GoatQuery::ORDER_ASC);
        }

        $select->range($query->getLimit(), $query->getOffset());
    }

    /**
     * Apply simple LIKE based text search on field
     */
    final protected function applyLikeSearch(Query $query, SelectQuery $select, $prefix = false)
    {
        $inputDefinition = $query->getInputDefinition();

        $searchString = $query->getSearchString();
        if ($searchString) {
            if ($inputDefinition->hasSearchField()) {

                $escapedLike = $this->mapper->getRunner()->getEscaper()->escapeLike($searchString);
                $orClause = $select->getWhere()->or();

                foreach ($inputDefinition->getSearchFields() as $field) {
                    if ($prefix) {
                        $orClause->isLike($field, '%'.$escapedLike.'%', false);
                    } else {
                        $orClause->isLike($field, $escapedLike.'%', false);
                    }
                }
            }
        }
    }

    /**
     * Apply filters on Doctrine query
     */
    protected function applyFilters(Query $query, SelectQuery $select)
    {
        $this->applyLikeSearch($query, $select);

        /*
        foreach ($this->allowedFilters as $property) {
            if ($query->has($property)) {
                $value = $query->get($property);
                if (is_array($value)) {
                    $select->where('entity.'.$property.' IN (:' . $property . ')')->setParameter($property, $value);
                } else {
                    $select->where('entity.'.$property.' = :' . $property)->setParameter($property, $value);
                }
            }
        }

        // Find searchable fields
        $searchables = [];
        foreach ($this->classMetadata->getFieldNames() as $property) {
            switch ($this->classMetadata->getTypeOfField($property)) {
                case DBALType::STRING:
                case DBALType::TEXT:
                    $searchables[] = $property;
                    break;
            }
        }

        $searchString = $query->getSearchString();
        if ($searchString && $searchables) {
            if ($inputDefinition->hasSearchField()) {
                $searchables = array_intersect($inputDefinition->getSearchFields(), $searchables);
            }

            // @todo should we break if there is no search field?
            $orClause = [];
            foreach ($searchables as $i => $property) {
                $orClause[] = sprintf("entity.%s LIKE :search%d", $property, $i);
                $select->setParameter('search'.$i, '%'.addcslashes($searchString, '\%_').'%');
            }
            $select->andWhere(implode(' or ', $orClause));
        }
         */
    }

    /**
     * {@inheritdoc}
     */
    final public function getItems(Query $query)
    {
        $select = $this->createQuery();
        $this->applyFilters($query, $select);
        $this->processQuery($query, $select);

        $className = $this->mapper->getClassName();
        $result = new GoatDatasourceResult($className, $select->execute([], $className));

        if ($query->getInputDefinition()->isPagerEnabled()) {
            $result->setTotalItemCount($select->getCountQuery()->execute()->fetchField());
        }

        return $result;
    }
}
