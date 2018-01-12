<?php

namespace MakinaCorpus\Calista\Bridge\Goat;

use Goat\Runner\ResultIteratorInterface;
use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Datasource\DatasourceResultTrait;
use MakinaCorpus\Calista\Datasource\PropertyDescription;
use MakinaCorpus\Calista\Error\ConfigurationError;

/**
 * Basics for the datasource result interface implementation
 */
class GoatDatasourceResult implements \IteratorAggregate, DatasourceResultInterface
{
    use DatasourceResultTrait;

    private $result;

    /**
     * Default constructor
     *
     * @param string $itemClass
     * @param ResultIteratorInterface $items
     * @param array PropertyDescription[]
     */
    public function __construct($itemClass, ResultIteratorInterface $result, array $properties = [])
    {
        $this->itemClass = $itemClass;
        $this->result = $result;

        foreach ($properties as $index => $property) {
            if (!$property instanceof PropertyDescription) {
                throw new ConfigurationError(sprintf("property at index %s is not a %s instance", $index, PropertyDescription::class));
            }
        }

        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function canStream()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->result->countRows();
    }
}
