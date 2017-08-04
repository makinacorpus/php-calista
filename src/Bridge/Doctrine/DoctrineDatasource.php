<?php

namespace MakinaCorpus\Calista\Bridge\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Calista\Error\CalistaError;

/**
 * Integrates doctrine entity managers as datasources.
 *
 * For property info extraction, use the Doctrine brige provider in Symfony
 * fullstack framework, which will be automatically registered by doctrine's
 * own DoctrineBundle if enabled in your application.
 *
 * This datasource will not be automatically registered, instead you will
 * need to register one per entity class you need to.
 */
class DoctrineDatasource extends AbstractDatasource
{
    private $allowedAssociationFilters = [];
    private $allowedFilters = [];
    private $entityName;
    private $primaryKey = [];
    private $classMetadata;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    private $repository;

    /**
     * Default constructor
     *
     * @param string $entityName
     *   Doctrine entity name, or class name
     * @param Registry $doctrineRegistry
     *   Doctrine registry
     * @param array $allowedAssociationFilters
     *   Array of entity properties, that are associations, which will be
     *   loaded in order to fetch allowed values.
     */
    public function __construct($entityName, Registry $doctrineRegistry, array $allowedAssociationFilters = [])
    {
        $this->entityName = $entityName;
        $this->repository = $doctrineRegistry->getRepository($this->entityName);
        $this->allowedAssociationFilters = $allowedAssociationFilters;
        $this->classMetadata = $doctrineRegistry->getManager()->getClassMetadata($this->entityName);
        $this->primaryKey = $this->classMetadata->getIdentifierFieldNames();
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return $this->repository->getClassName();
    }

    /**
     * Is property filterable
     *
     * @return bool
     */
    private function isPropertyFilterable(ClassMetadata $metadata, $property)
    {
        $typeOfField = $metadata->getTypeOfField($property);

        switch ($typeOfField) {

            case DBALType::BLOB:
            case 'binary':
                break;

            case DBALType::OBJECT:
                break;

            case DBALType::TARRAY:
            case DBALType::SIMPLE_ARRAY:
            case DBALType::JSON_ARRAY:
                break;

            case DBALType::DATE:
            case DBALType::DATETIME:
            case DBALType::DATETIMETZ:
            case 'vardatetime':
            case DBALType::TIME:
                // Fallthought, because date can be filtered.

            default:
                return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        $ret = [];

        foreach ($this->classMetadata->getFieldNames() as $property) {
            if ($this->isPropertyFilterable($this->classMetadata, $property)) {
                $this->allowedFilters[] = $property;
                $ret[] = new Filter($property);
            }
        }


        /*
         * @todo keeping this for later (this code is from DoctrineExtractor
         *   implementation) - we'll deal with associations when we'll need
         *   it; this means that as of now, filtering using associations is
         *   not possible.
         *
        foreach ($metadata->getAssociationNames() as $property) {

            if (!in_array($property, $this->allowedAssociationFilters)) {
                // @todo
            }

            if ($metadata->hasAssociation($property)) {
                $class = $metadata->getAssociationTargetClass($property);

                if ($metadata->isSingleValuedAssociation($property)) {
                    if ($metadata instanceof ClassMetadataInfo) {
                        $associationMapping = $metadata->getAssociationMapping($property);

                        $nullable = $this->isAssociationNullable($associationMapping);
                    } else {
                        $nullable = false;
                    }

                    return array(new Type(Type::BUILTIN_TYPE_OBJECT, $nullable, $class));
                }

                $collectionKeyType = DBALType::class;

                if ($metadata instanceof ClassMetadataInfo) {
                    $associationMapping = $metadata->getAssociationMapping($property);

                    if (isset($associationMapping['indexBy'])) {
                        $indexProperty = $associationMapping['indexBy'];
                        $subMetadata = $this->classMetadataFactory->getMetadataFor($associationMapping['targetEntity']);
                        $typeOfField = $subMetadata->getTypeOfField($indexProperty);

                        $collectionKeyType = $this->getPhpType($typeOfField);
                    }
                }

                return array(new Type(
                    Type::BUILTIN_TYPE_OBJECT,
                    false,
                    'Doctrine\Common\Collections\Collection',
                    true,
                    new Type($collectionKeyType),
                    new Type(Type::BUILTIN_TYPE_OBJECT, false, $class)
                ));
            }
        }
         */

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
    {
        $ret = [];

        foreach ($this->classMetadata->getFieldNames() as $property) {
            if ($this->isPropertyFilterable($this->classMetadata, $property)) {
                $ret[$property] = $property;
            }
        }

        return $ret;
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
        $primaryKeyLength = count($this->primaryKey);
        if (!$primaryKeyLength || 1 < $primaryKeyLength) {
            throw new CalistaError("doctrine datasource cannot validate entities with no primary key or with multiple columns primary key");
        }

        $idList = array_unique($idList);

        $select = $this->createQuery();
        $this->applyFilters($query, $select);

        $primaryKeyAlias = 'entity.' . reset($this->primaryKey);
        $databaseIdList = $select
            ->select($primaryKeyAlias)
            ->where($primaryKeyAlias . ' IN (:idList)')
            ->setParameter('idList', $idList)
            ->getQuery()
            ->getArrayResult()
        ;

        return array_diff($idList, $databaseIdList) ? false : true;
    }

    /**
     * Create Doctrine DBAL query
     *
     * @return QueryBuilder
     */
    private function createQuery()
    {
        return $this->repository->createQueryBuilder('entity');
    }

    /**
     * Apply pagination and ordering
     */
    private function processQuery(Query $query, QueryBuilder $select)
    {
        if ($query->hasSortField()) {
            $select->orderBy('entity.'.$query->getSortField(), $query->getSortOrder());
        }

        $limit  = $query->getLimit();
        $offset = $query->getOffset();

        $select->setFirstResult($offset)->setMaxResults($limit);
    }

    /**
     * Apply filters on Doctrine query
     */
    private function applyFilters(Query $query, QueryBuilder $select)
    {
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
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $select = $this->createQuery();
        $this->applyFilters($query, $select);
        $this->processQuery($query, $select);

        if ($query->getInputDefinition()->isPagerEnabled()) {
            $paginator = new Paginator($select, true);

            return $this->createResult($paginator, $paginator->count());
        }

        return $this->createResult($select->getQuery()->getResult());
    }
}
