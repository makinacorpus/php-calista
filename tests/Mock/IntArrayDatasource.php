<?php

namespace MakinaCorpus\Calista\Tests\Mock;

use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DefaultDatasourceResult;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;

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
    public function getItemClass()
    {
        return IntItem::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
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
    public function getSorts()
    {
        return [
            'value' => "Value",
            'odd_or_even' => 'Odd first',
        ];
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

        if ($query->hasSortField()) {
            if ('value' === $query->getSortField()) {
                if (Query::SORT_DESC === $query->getSortOrder()) {
                    $allowedValues = array_reverse($allowedValues);
                }
            }
            if ('odd_or_even' === $query->getSortField()) {
                if (Query::SORT_DESC === $query->getSortOrder()) {
                    // Not implemented yet
                } else {
                    // Not implemented yet
                }
            }
        }

        $items = array_slice($allowedValues, $offset, $limit);
        $items = array_map(function ($value) { return new IntItem($value); }, $items);

        $result = new DefaultDatasourceResult(IntItem::class, $items);
        $result->setTotalItemCount(count($allowedValues));

        return $result;
    }
}
