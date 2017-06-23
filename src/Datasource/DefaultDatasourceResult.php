<?php

namespace MakinaCorpus\Dashboard\Datasource;

/**
 * Basics for the datasource result interface implementation
 */
class DefaultDatasourceResult implements \IteratorAggregate, DatasourceResultInterface
{
    use DatasourceResultTrait;

    private $items;
    private $count;

    /**
     * Default constructor
     *
     * @param string $itemClass
     * @param array|\Traversable $items
     */
    public function __construct($itemClass, $items)
    {
        $this->itemClass = $itemClass;
        $this->items = $items;
    }

    /**
     * {@inheritdoc}
     */
    public function canStream()
    {
        // Having an array here would mean data has been preloaded hence it is
        // not gracefully streamed from the real datasource.
        return !is_array($this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if ($this->items instanceof \Traversable) {
            return $this->items;
        }

        if (is_array($this->items)) {
            return new \ArrayIterator($this->items);
        }

        return new \EmptyIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (null !== $this->count) {
            return $this->count;
        }

        if (is_array($this->items) || $this->items instanceof \Countable) {
            return $this->count = count($this->items);
        }

        return $this->count = 0;
    }
}
