<?php

namespace ccennis\Larelastic\Services;

class NestedQueryService
{
    private static function getNestpath($field)
    {
        $arr = explode(".", $field);
        if (count($arr) > 1) {
            array_pop($arr);
            return implode(".",$arr);
        }
        return $arr;
    }

    //todo check for raw flag
    public static function buildQuery($data){

        $search_string = [];
        $nestPath = self::getNestpath($data['field']);
        $data['field'] = str_replace($nestPath . ".", "", $data['field']);

        switch ($data['operator']) {
            case 'eq':

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => array(['match' => [
                                $nestPath . "." . $data['field'] => $data['value']
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
                                        'fields' => [$nestPath .".". $data['field']]
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
                                        'fields' => [$nestPath .".". $data['field']]
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
                                    'fields' => [$nestPath . "." . $data['field']]
                                ],
                            ]
                        ]]
                ];
                break;
            case "gte":
            case "lte":
            case "gt":
            case "lt":

                $range[]['range'][$nestPath . $data['field']] = [$data['operator'] => $data['value']];

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'range' => [
                                    $nestPath .".".  $data['field'] => $range
                                ]
                            ]
                        ]
                    ]
                ];

                break;

            case "between":
                $range[]['range'][$nestPath . $data['field']] = ['gte' => $data['value1'], 'lte' => $data['value2']];

                $search_string[]['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'range' => [
                                    $nestPath .".". $data['field'] => $range
                                ]
                            ]
                        ]
                    ]
                ];
                break;

            case "exists":

                $search_string = ['exists' => [
                    "field" => $nestPath .".". $data['field'],
                ]];
                break;
        }
        return $search_string;
    }

    public static function buildSort($data)
    {
        //raw or keyword, for example
        $fieldType = isset($data['field_type']) ? ".".$data['field_type'] : "";

        if (isset($data['field'])) {
            $nestedPath = $data['field'].$fieldType;
            return array(
                $nestedPath => [
                    'missing' => $data['missing'] ?? "_last",
                    'order' => $data['order'],
                    'nested_path' => substr($nestedPath, 0, strrpos($nestedPath, "."))
                ]
            );
        }
    }
}