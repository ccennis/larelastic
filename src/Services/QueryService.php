<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/10/19
 * Time: 2:16 PM
 */

namespace ccennis\Larelastic\Services;

class QueryService
{
    //todo check for raw flag
    public static function buildQuery($data, $nestPath = null)
    {
        $search_string = [];

        if(isset($data['column'])){
            switch ($data['operator']) {
                case 'eq':

                    $search_string = ['match' => [
                        $nestPath . $data['column'] => $data['value'],
                    ]];

                    break;

                case "begins_with":
                    $search_string =
                        [
                            'query_string' => [
                                'query' => $data['value'] . '*',
                                'fields' => [$nestPath . $data['column']]
                            ]
                        ];
                    break;

                case "ends_with":
                    $search_string =
                        [
                            'query_string' => [
                                'query' => '*' . $data['value'],
                                'fields' => [$nestPath . $data['column']]
                            ]

                        ];

                    break;
                case "contains":
                    $search_string = [
                        'query_string' => [
                            'query' => '*' . $data['value'] . '*',
                            'fields' => [$nestPath . $data['column']]
                        ],

                    ];
                    break;
                case "gte":
                case "lte":
                case "gt":
                case "lt":

                    $range[]['range'][$nestPath . $data['column']] = [$data['operator'] => $data['value']];
                    $search_string = $range;

                    break;

                case "between":
                    $range[]['range'][$nestPath . $data['column']] = ['gte' => $data['value1'], 'lte' => $data['value2']];
                    $search_string = [$range];
                    break;
            }
        }
        return $search_string;
    }

    public function getByIds($ids)
    {
        $query = array(
            'ids' => [
                'type' => 'items',
                'values' =>
                    $ids
            ]
        );

        return $query;
    }

    public static function buildShouldQuery($data)
    {
        $should = [];

        foreach ($data['clauses'] as $clause) {

            $array[] = (self::buildQuery($clause));
        }

        $should = [
            'bool' => [
                'should' =>
                    $array,
                'minimum_should_match' => 1
            ],
        ];

        return $should;
    }

    public static function buildFilterQuery($data)
    {
        $filter = [];

        foreach ($data['clauses'] as $clause) {

            $array[] = (self::buildQuery($clause)
            );
        }

        $filter = $array;

        return $filter;
    }
    
    public static function buildSort($data)
    {
        if (isset($data['field'])) {
            return array(
                $data['field'] => [
                    'order' => $data['order']
                ]
            );
        }
    }
}