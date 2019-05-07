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

                $search_string[]['nested'] = [
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

                $search_string[]['nested'] = [
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

                break;

            case "exists":
                $search_string[]['nested'] = [
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
        }
        return $search_string;
    }

    public static function buildAgg($data)
    {
        $nestPath = self::getNestpath($data['histogram_field']);
        $agg_body = [];

        if (isset($data['agg_type'])) {
            switch ($data['agg_type']) {

                //technically this would be "bucket" aggregation.
                case 'histogram':

                    $agg_body = [
                        $data['agg_name'] => [
                            'field' => $data['field'],
                            'interval' => $data['interval'],
                            'min_doc_count' => $data['min_doc_count'] ?? 0,
                            'extended_bounds' => [
                                "min" => $data['bounds_min'],
                                "max" => $data['bounds_max']
                            ]
                        ]
                    ];
                    break;

                //i.e. sum, count, avg
                case 'metric_aggregation':

                    $agg_body = [
                        $data['agg_name'] => [
                            $data['operator'] => [
                                'field' => $data['value']
                            ]
                        ]
                    ];

                    break;

                case 'pipeline':

                    $bucket_type = $data['bucket_type'] . "_bucket";

                    $agg_body = [
                        'root' => [ 'nested' =>
                            ['path' => $nestPath ],
                            'aggs' => [
                                //i.e. "monthly_sales"
                                $data['bucket_name'] => [
                                    'date_histogram' => [
                                        'field' => $data['histogram_field'],
                                        'interval' => $data['interval'],
                                        'min_doc_count' => $data['min_doc_count'] ?? 0,
                                        'extended_bounds' => [
                                            "min" => $data['bounds_min'],
                                            "max" => $data['bounds_max']
                                        ]
                                    ],
                                    'aggs' => [
                                        $data['bucket_field'] => [
                                            $data['bucket_type'] => [
                                                'field' => $data['agg_field']
                                            ]
                                        ]
                                    ]
                                ],
                                $data['bucket_type'] => [
                                    //i.e. sum_bucket/ max_bucket
                                    $bucket_type => [
                                        'buckets_path' => $data['bucket_name'] . ">" . $data['bucket_field']
                                    ]
                                ]
                            ]
                        ]
                    ];
                    break;
            }
        }
        return $agg_body;
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