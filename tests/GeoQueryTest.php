<?php

namespace Larelastic\Elastic\Tests;

use Larelastic\Elastic\Services\NestedQueryService;
use Larelastic\Elastic\Services\QueryService;

/**
 * Geo queries had no test coverage, so the Elasticsearch 8 upgrade dropped
 * buildGeoQuery from both services and nothing noticed. QueryBuilder kept
 * calling it, and every distance search fataled with an undefined method.
 */
class GeoQueryTest extends AbstractTestCase
{
    public function test_build_geo_query(): void
    {
        $result = QueryService::buildGeoQuery([
            'col' => 'location_lat_long',
            'lat' => 38.2527,
            'lon' => -85.7585,
            'distance' => '50mi',
        ]);

        $this->assertEquals([
            'geo_distance' => [
                'distance' => '50mi',
                'location_lat_long' => [
                    'lat' => 38.2527,
                    'lon' => -85.7585,
                ],
            ],
        ], $result);
    }

    public function test_build_nested_geo_query(): void
    {
        $result = NestedQueryService::buildGeoQuery([
            'col' => 'studio.location_lat_long',
            'lat' => 38.2527,
            'lon' => -85.7585,
            'distance' => '25km',
        ]);

        $this->assertEquals([
            'nested' => [
                'path' => 'studio',
                'query' => [
                    'geo_distance' => [
                        'distance' => '25km',
                        'studio.location_lat_long' => [
                            'lat' => 38.2527,
                            'lon' => -85.7585,
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function test_build_geo_sort(): void
    {
        $result = QueryService::buildGeoSort([
            'field' => 'location_lat_long',
            'lat' => 38.2527,
            'lon' => -85.7585,
        ]);

        $this->assertSame('asc', $result['_geo_distance']['order']);
        $this->assertSame('mi', $result['_geo_distance']['unit']);
        $this->assertEquals(
            ['lat' => 38.2527, 'lon' => -85.7585],
            $result['_geo_distance']['location_lat_long']
        );
    }

    public function test_build_nested_geo_sort_carries_the_path(): void
    {
        $result = NestedQueryService::buildGeoSort([
            'field' => 'studio.location_lat_long',
            'lat' => 38.2527,
            'lon' => -85.7585,
        ]);

        $this->assertSame('studio', $result['_geo_distance']['nested']['path']);
    }
}
