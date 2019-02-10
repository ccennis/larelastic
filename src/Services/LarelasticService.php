<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/9/19
 * Time: 2:24 PM
 */

namespace cennis\larelastic\Services;


class LarelasticService
{
    //$data param must contain a column, value, and operator
    public function buildQuery($data, $nestPath = null)
    {
        $must = [];

        if(isset($data['column'])){
            switch ($data['operator']) {
                case '=':

                    $must = ['match' => [
                        $nestPath . $data['column'] => $data['value'],
                    ]];

                    break;

                case "begins_with":
                    $must =
                        [
                            'query_string' => [
                                'query' => $data['value'] . '*',
                                'fields' => [$nestPath . $data['column']]
                            ]
                        ];
                    break;

                case "ends_with":
                    $must =
                        [
                            'query_string' => [
                                'query' => '*' . $data['value'],
                                'fields' => [$nestPath . $data['column']]
                            ]

                        ];

                    break;
                case "contains":

                    if ($data['column'] == 'crm_user_id') {
                        $match = array();
                        foreach ($data['value'] as $id) {
                            // "['crm_user_id' => "."'".$id."']";
                            $match[] = array(
                                'match' => array(
                                    $nestPath . 'crm_user_id' => $id
                                )
                            );
                        }
                        $must = [
                            'bool' => [
                                'should' =>
                                    $match,
                                'minimum_should_match' => 1
                            ],

                        ];
                    } else {
                        $must = [
                            'query_string' => [
                                'query' => '*' . $data['value'] . '*',
                                'fields' => [$nestPath . $data['column']]
                            ],

                        ];
                    }
                    break;
                case "gte":
                case "lte":
                case "gt":
                case "lt":

                    $range[]['range'][$nestPath . $data['column']] = [$data['operator'] => $data['value']];
                    $must = $range;

                    break;

                case "between":
                    $range[]['range'][$nestPath . $data['column']] = ['gte' => $data['value1'], 'lte' => $data['value2']];
                    $must = [$range];
                    break;
            }
        }
        return $must;
    }

    public function buildNestedQuery($data, $nestPath = null){

        $must = [];

        switch ($data['operator']) {
            case '=':

                $must[]['nested'] = [
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

                $must[]['nested'] = [
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

                $must[]['nested'] = [
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

                $must[]['nested'] = [
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

                $must[]['nested'] = [
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

                $must[]['nested'] = [
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
        return $must;
    }
}