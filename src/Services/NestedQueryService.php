<?php

namespace Larelastic\Elastic\Services;

use function is_array;
use function sprintf;

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

            case 'in':
                $search_string['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => array(['terms' => [
                                $nestPath . "." . $data['field'] => $data['value']
                            ]])
                        ]
                    ]
                ];
                break;

            case '=':
            case '<>':

                $search_string['nested'] = [
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

                $search_string['nested'] = [
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

                $search_string['nested'] = [
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

                $search_string['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'query_string' => [
                                    'query' => $data['value'],
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

                $search_string['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'range' => [
                                    $nestPath .".".$data['field'] => [
                                        $data['operator'] => $data['value']]
                                ]
                            ]
                        ]
                    ]
                ];

                break;

            case "between":
                if (is_array($data['value'])) {
                    $search_string['nested'] = [
                        'path' => $nestPath,
                        'query' => [
                            'bool' => [
                                'must' => [
                                    'range' => [
                                        $nestPath . "." . $data['field'] => [
                                            ['gte' => $data['value1'], 'lte' => $data['value2']]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        'The `between` operator requires a value array'));
                }
                break;

            case "exists":
                $search_string['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => [
                                'exists' => [
                                    'field' => $nestPath . "." . $data['field']
                                ]
                            ]
                        ]
                    ]
                ];
                break;

            case 'wildcard':

                $search_string['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => [
                            'must' => array(['wildcard' => [
                                $nestPath . "." . $data['field'] => $data['value']
                            ]])
                        ]
                    ]
                ];
                break;

            case 'phrase':

                $search_string['nested'] = [
                    'path' => $nestPath,
                    'query' => [
                        'bool' => ['match_phrase' => [
                            $data['field'] => $data['value'],
                        ]]
                    ]
                ];
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
                    'nested' => [
                        "path" => substr($nestedPath, 0, strrpos($nestedPath, "."))
                    ]
                ]
            );
        }
    }
}
