<?php

namespace ccennis\Larelastic\Services;

class QueryService
{
    //todo check for raw flag
    public static function buildQuery($data)
    {
        $search_string = [];

        if(isset($data['field'])){
            switch ($data['operator']) {

                case 'in':
                    $search_string = [$data['field'] => [
                        'values' => $data['value'],
                    ]];

                    break;

                case 'eq':

                    $search_string = ['match' => [
                        $data['field'] => $data['value'],
                    ]];

                    break;

                case "begins_with":
                    $search_string =
                        [
                            'query_string' => [
                                'query' => $data['value'] . '*',
                                'fields' => [$data['field']]
                            ]
                        ];
                    break;

                case "ends_with":
                    $search_string =
                        [
                            'query_string' => [
                                'query' => '*' . $data['value'],
                                'fields' => [$data['field']]
                            ]

                        ];

                    break;
                case "contains":
                    $search_string = [
                        'query_string' => [
                            'query' => '*' . $data['value'] . '*',
                            'fields' => [$data['field']]
                        ],

                    ];
                    break;
                case "gte":
                case "lte":
                case "gt":
                case "lt":

                    $range[]['range'][$data['field']] = [$data['operator'] => $data['value']];
                    $search_string = $range;

                    break;

                case "between":
                    $range[]['range'][$data['field']] = ['gte' => $data['value1'], 'lte' => $data['value2']];
                    $search_string = $range;
                    break;

                case "exists":

                    $search_string = ['exists' => [
                        "field" => $data['field'],
                    ]];
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
        //raw or keyword, for example
        $fieldType = isset($data['field_type']) ? ".".$data['field_type'] : "";

        if (isset($data['field'])) {
            return array(
                $data['field'].$fieldType => [
                    'missing' => $data['missing'] ?? "_last",
                    'order' => $data['order']
                ]
            );
        }
    }
}