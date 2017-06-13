<?php

namespace MakinaCorpus\Dashboard\Tests\Mock;

use MakinaCorpus\Dashboard\Datasource\AbstractDatasource;
use MakinaCorpus\Dashboard\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Dashboard\Datasource\Query;
use MakinaCorpus\Dashboard\Page\Filter;
use MakinaCorpus\Dashboard\Page\SortCollection;

/**
 * Uses an array as datasource
 */
class IntArrayDatasource extends AbstractDatasource
{
    private $values;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->values = range(1, 255);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(Query $query)
    {
        return [
            (new Filter('odd_or_even', "Odd or Even"))->setChoicesMap([
                'odd' => "Odd",
                'even' => "Even",
            ]),
            (new Filter('mod3', "Modulo 3"))->setChoicesMap([
                1 => "Yes",
                0 => "No",
            ]),
            (new Filter('modX', "Modulo X"))->setChoicesMap(array_combine(range(0, 10), range(0, 10))),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(Query $query)
    {
        return new SortCollection(
            [
                'value' => "Value",
            ],
            'value',
            Query::SORT_ASC
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        $limit = $query->getLimit();
        $offset = $query->getOffset();
        $allowedValues = $this->values;

        if ($query->has('odd_or_even')) {
            switch ($query->get('odd_or_even')) {

                case 'odd':
                    $allowedValues = array_filter($allowedValues, function ($value) {
                        return 1 === $value % 2;
                    });
                    break;

                case 'even':
                    $allowedValues = array_filter($allowedValues, function ($value) {
                        return 0 === $value % 2;
                    });
                    break;

                default:
                    $allowedValues = [];
                    break;
            }
        }

        if ($query->has('mod3')) {
            switch ($query->get('mod3')) {

                case 1:
                    $allowedValues = array_filter($allowedValues, function ($value) {
                        return 0 === $value % 3;
                    });
                    break;

                case 0:
                    $allowedValues = array_filter($allowedValues, function ($value) {
                        return 1 === $value % 3;
                    });
                    break;

                default:
                    $allowedValues = [];
                    break;
            }
        }

        if ($query->hasSortField() && 'value' === $query->getSortField()) {
            if (Query::SORT_DESC === $query->getSortOrder()) {
                $allowedValues = array_reverse($allowedValues);
            }
        }

        $result = new DefaultDatasourceResult(array_slice($allowedValues, $offset, $limit));
        $result->setTotalItemCount(count($allowedValues));

        return $result;
    }
}
