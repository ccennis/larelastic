<?php

namespace Larelastic\Elastic\Services;


class QueryService
{
    /**
     * @param $data
     * @return array
     * @throws \InvalidArgumentException on invalid query arguments
     */
    public static function buildQuery($data)
    {
        $search_string = [];

        if (isset($data['field'])) {
            switch ($data['operator']) {

                case 'in':
                    $search_string = ['terms' => [
                        $data['field'] => $data['value']
                    ]];

                    break;

                case '=':
                case '<>':

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
                            'query' => $data['value'],
                            'fields' => [$data['field']]
                        ],

                    ];
                    break;
                case "gte":
                case "lte":
                case "gt":
                case "lt":

                    $range['range'][$data['field']] = [$data['operator'] => $data['value']];
                    $search_string = $range;

                    break;

                case "between":
                    if (is_array($data['value'])) {
                        $range['range'][$data['field']] = ['gte' => $data['value'][0], 'lte' => $data['value'][1]];
                        $search_string = $range;
                    } else {
                        throw new \InvalidArgumentException(sprintf(
                            'The `between` operator requires a value array'));
                    }
                    break;

                case "exists":
                    $search_string = ['exists' => [
                        "field" => $data['field'],
                    ]];
                    break;

                case 'wildcard':
                case 'wildcard_not':
                    $search_string = ['wildcard' => [
                        $data['field'] => [
                            'value' => $data['value'],
                            'boost' => 1,
                            'rewrite' => 'constant_score'
                        ]]];

                    break;

                case 'phrase':
                    $search_string = ['match_phrase' => [
                        $data['field'] => $data['value'],

                    ]];
                    break;
            }
        }
        return $search_string;
    }

    /**
     * @param $data
     * @return array
     */
    public static function buildAgg($data)
    {
        $search_string =
            [
                "agg_field" => [

                    "terms" => [
                        "field" => $data['field']
                    ]
                ]
            ];

        return $search_string;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function getByIds(array $ids)
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

    /**
     * @param $data
     * @return array
     */
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

    public static function buildGeoQuery($data)
    {
        $geoQuery = [
            'geo_distance' => [
                'distance' => $data['distance'],
                $data['col'] => [
                    "lat" => $data['lat'],
                    "lon" => $data['lon']
                ],
            ]];

        return $geoQuery;
    }


    /**
     * @param $data
     * @return array
     */
    public static function buildSort($data)
    {
        $reservedSorts = ["_score", "_doc"];

        if (in_array($data['field'], $reservedSorts)) {
            return $data['field'];
        }

        //raw or keyword, for example
        $fieldType = isset($data['field_type']) ? "." . $data['field_type'] : "";

        if (isset($data['field'])) {
            return array(
                $data['field'] . $fieldType => [
                    'missing' => $data['missing'] ?? "_last",
                    'order' => $data['order'] ?? 'asc'
                ]
            );
        }
    }


    /**
     * @param $data
     * @return array
     */
    public static function buildGeoSort($data)
    {
        if (isset($data['field'])) {
            return array(
                "_geo_distance" => [
                    $data['field'] => [
                        "lat" => $data['lat'],
                        "lon" => $data['lon']
                    ],
                    "order" => $data['order'] ?? 'asc',
                    "unit" => $data['unit'] ?? 'mi',
                    "mode" => $data['mode'] ?? 'min',
                    "distance_type" => $data['distance_type'] ?? 'arc',
                    "ignore_unmapped" => $data['ignore_unmapped'] ?? false
                ]
            );
        }
    }

    /**
     * @param $data
     * @return array
     */
    public static function buildShould($clauses, $minMatch = 1)
    {
        $search_string =
            ['bool' => [
                "minimum_should_match" => $minMatch,
                "should" => $clauses
            ]];

        return $search_string;
    }
}