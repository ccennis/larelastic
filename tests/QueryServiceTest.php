<?php

namespace Larelastic\Elastic\Tests;

use Larelastic\Elastic\Services\QueryService;

class QueryServiceTest extends AbstractTestCase
{
    // --- buildQuery ---

    public function test_build_match_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'name',
            'operator' => '=',
            'value' => 'test',
        ]);

        $this->assertEquals(['match' => ['name' => 'test']], $result);
    }

    public function test_build_not_equal_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'name',
            'operator' => '<>',
            'value' => 'test',
        ]);

        $this->assertEquals(['match' => ['name' => 'test']], $result);
    }

    public function test_build_terms_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'status',
            'operator' => 'in',
            'value' => ['active', 'pending'],
        ]);

        $this->assertEquals(['terms' => ['status' => ['active', 'pending']]], $result);
    }

    public function test_build_begins_with_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'name',
            'operator' => 'begins_with',
            'value' => 'foo',
        ]);

        $this->assertArrayHasKey('query_string', $result);
        $this->assertEquals('foo*', $result['query_string']['query']);
        $this->assertEquals(['name'], $result['query_string']['fields']);
    }

    public function test_build_ends_with_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'name',
            'operator' => 'ends_with',
            'value' => 'bar',
        ]);

        $this->assertArrayHasKey('query_string', $result);
        $this->assertEquals('*bar', $result['query_string']['query']);
    }

    public function test_build_contains_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'name',
            'operator' => 'contains',
            'value' => 'baz',
        ]);

        $this->assertArrayHasKey('query_string', $result);
        $this->assertEquals('baz', $result['query_string']['query']);
    }

    public function test_build_gte_range_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'price',
            'operator' => 'gte',
            'value' => 100,
        ]);

        $this->assertEquals(['range' => ['price' => ['gte' => 100]]], $result);
    }

    public function test_build_lte_range_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'price',
            'operator' => 'lte',
            'value' => 500,
        ]);

        $this->assertEquals(['range' => ['price' => ['lte' => 500]]], $result);
    }

    public function test_build_between_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'price',
            'operator' => 'between',
            'value' => [10, 100],
        ]);

        $this->assertEquals(['range' => ['price' => ['gte' => 10, 'lte' => 100]]], $result);
    }

    public function test_build_between_requires_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        QueryService::buildQuery([
            'field' => 'price',
            'operator' => 'between',
            'value' => 'not_an_array',
        ]);
    }

    public function test_build_exists_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'image',
            'operator' => 'exists',
            'value' => true,
        ]);

        $this->assertEquals(['exists' => ['field' => 'image']], $result);
    }

    public function test_build_wildcard_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'name',
            'operator' => 'wildcard',
            'value' => '*test*',
        ]);

        $this->assertArrayHasKey('wildcard', $result);
        $this->assertEquals('*test*', $result['wildcard']['name']['value']);
    }

    public function test_build_phrase_query(): void
    {
        $result = QueryService::buildQuery([
            'field' => 'bio',
            'operator' => 'phrase',
            'value' => 'exact match',
        ]);

        $this->assertEquals(['match_phrase' => ['bio' => 'exact match']], $result);
    }

    public function test_build_query_returns_empty_without_field(): void
    {
        $result = QueryService::buildQuery([
            'operator' => '=',
            'value' => 'test',
        ]);

        $this->assertEquals([], $result);
    }

    // --- buildAgg ---

    public function test_build_agg(): void
    {
        $result = QueryService::buildAgg(['field' => 'status']);

        $this->assertEquals([
            'agg_field' => ['terms' => ['field' => 'status']],
        ], $result);
    }

    // --- getByIds ---

    public function test_get_by_ids(): void
    {
        $service = new QueryService();
        $result = $service->getByIds([1, 2, 3]);

        $this->assertEquals(['ids' => ['values' => [1, 2, 3]]], $result);
    }

    public function test_get_by_ids_has_no_type(): void
    {
        $service = new QueryService();
        $result = $service->getByIds([1]);

        $this->assertArrayNotHasKey('type', $result['ids']);
    }

    // --- buildSort ---

    public function test_build_sort(): void
    {
        $result = QueryService::buildSort([
            'field' => 'created_at',
            'order' => 'desc',
        ]);

        $this->assertEquals([
            'created_at' => ['missing' => '_last', 'order' => 'desc'],
        ], $result);
    }

    public function test_build_sort_with_field_type(): void
    {
        $result = QueryService::buildSort([
            'field' => 'name',
            'order' => 'asc',
            'field_type' => 'keyword',
        ]);

        $this->assertArrayHasKey('name.keyword', $result);
    }

    public function test_build_sort_reserved_score(): void
    {
        $result = QueryService::buildSort([
            'field' => '_score',
        ]);

        $this->assertEquals('_score', $result);
    }

    public function test_build_sort_reserved_doc(): void
    {
        $result = QueryService::buildSort([
            'field' => '_doc',
        ]);

        $this->assertEquals('_doc', $result);
    }

    // --- buildShould ---

    public function test_build_should(): void
    {
        $clauses = [
            ['match' => ['col1' => 'val1']],
            ['match' => ['col2' => 'val2']],
        ];

        $result = QueryService::buildShould($clauses, 1);

        $this->assertEquals([
            'bool' => [
                'minimum_should_match' => 1,
                'should' => $clauses,
            ],
        ], $result);
    }

    // --- buildFilterQuery ---

    public function test_build_filter_query(): void
    {
        $result = QueryService::buildFilterQuery([
            'clauses' => [
                ['field' => 'status', 'operator' => '=', 'value' => 'active'],
                ['field' => 'type', 'operator' => '=', 'value' => 'premium'],
            ],
        ]);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('match', $result[0]);
        $this->assertArrayHasKey('match', $result[1]);
    }

    // --- buildGeoSort ---

    public function test_build_geo_sort(): void
    {
        $result = QueryService::buildGeoSort([
            'field' => 'location',
            'lat' => 40.7128,
            'lon' => -74.0060,
        ]);

        $this->assertArrayHasKey('_geo_distance', $result);
        $this->assertEquals(40.7128, $result['_geo_distance']['location']['lat']);
        $this->assertEquals(-74.0060, $result['_geo_distance']['location']['lon']);
        $this->assertEquals('asc', $result['_geo_distance']['order']);
        $this->assertEquals('mi', $result['_geo_distance']['unit']);
    }
}
