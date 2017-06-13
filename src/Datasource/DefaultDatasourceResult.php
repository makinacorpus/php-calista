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
     * @param array|\Traversable $items
     */
    public function __construct($items)
    {
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

    /**
     * {@inheritdoc}
     */
    public function setTotalItemCount($count)
    {
        $this->totalCount = $count;
    }

    /**
     * {@inheritdoc}
     */
    public function hasTotalItemCount()
    {
        return null !== $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }
}
