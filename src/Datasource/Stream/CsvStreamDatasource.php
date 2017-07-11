<?php

namespace MakinaCorpus\Calista\Datasource\Stream;

use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Query;

/**
 * Decent CSV streamed reader, that will consume very low memory
 */
class CsvStreamDatasource extends AbstractDatasource
{
    private $filename;
    private $options;

    /**
     * Default constructor
     *
     * @param string $filename
     * @param string[] $options
     *   Options for the CsvStreamReader constructor
     */
    public function __construct($filename, array $options = [])
    {
        $this->filename = $filename;
        $this->options = $options;
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
        return false;
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
    public function getItems(Query $query)
    {
        $reader = new CsvStreamReader($this->filename, $this->options);

        return $this->createResult($reader, $reader->isCountReliable() ? count($reader) : null);
    }
}
