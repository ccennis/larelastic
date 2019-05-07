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

    public static function buildAgg($data)
    {

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

                    $bucket_type = $data['bucket_type']."_bucket";

                    $agg_body = [
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
                                'buckets_path' => $data['bucket_name'] .">". $data['bucket_field']
                            ]
                        ]

                    ];
                    break;
            }
        }
        return $agg_body;
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