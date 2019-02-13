<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/10/19
 * Time: 2:16 PM
 */

namespace ccennis\Larelastic\Services;

class NestedQueryService
{
    private static function getNestpath($field)
    {
        $arr = explode(".", $field);
        if (count($arr) > 1) {
            return array_shift($arr);
        }
    }

    //todo check for raw flag
    public static function buildQuery($data){

        $search_string = [];
        $nestPath = self::getNestpath($data['column']);
        $data['column'] = str_replace($nestPath . ".", "", $data['column']);

        switch ($data['operator']) {
            case 'eq':

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => array(['match' => [
                                $nestPath . "." . $data['column'] => $data['value']
                            ]])
                        ]
                    ]
                ];

                break;

            case "begins_with":

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'query_string' => [
                                        'query' => $data['value'] . '*',
                                        'fields' => [$nestPath .".". $data['column']]
                                    ]
                                ],
                            ]
                        ]]
                ];
                break;

            case "ends_with":

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                [
                                    'query_string' => [
                                        'query' => '*' . $data['value'],
                                        'fields' => [$nestPath .".". $data['column']]
                                    ]
                                ],
                            ]
                        ]]
                ];

                break;
            case "contains":

                $match = array();

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'query_string' => [
                                    'query' => '*' . $data['value'] . '*',
                                    'fields' => [$nestPath . "." . $data['column']]
                                ],
                            ]
                        ]]
                ];
                break;
            case "gte":
            case "lte":
            case "gt":
            case "lt":

                $range[]['range'][$nestPath . $data['column']] = [$data['operator'] => $data['value']];

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'range' => [
                                    $nestPath .".".  $data['column'] => $range
                                ]
                            ]
                        ]
                    ]
                ];

                break;

            case "between":
                $range[]['range'][$nestPath . $data['column']] = ['gte' => $data['value1'], 'lte' => $data['value2']];

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'range' => [
                                    $nestPath .".". $data['column'] => $range
                                ]
                            ]
                        ]
                    ]
                ];
                break;
        }
        return $search_string;
    }
    
    public static function buildSort($data)
    {
        if (isset($data['field'])) {
            $nestedPath = $data['field'];
            return array(
                $nestedPath => [
                    'order' => $data['order'],
                    'nested_path' => substr($nestedPath, 0, strrpos($nestedPath, "."))
                ]
            );
        }
    }
}