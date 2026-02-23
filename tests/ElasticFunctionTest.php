<?php

namespace Larelastic\Elastic\Tests;

use Larelastic\Elastic\Payloads\RawPayload;
use Larelastic\Elastic\Tests\Dependencies\QueryBuilder;
use function json_decode;
use function json_encode;

class ElasticFunctionTest extends AbstractTestCase
{
    private function buildPayload($builder): RawPayload
    {
        $params = json_decode(json_encode($builder->getQuery()), true);

        return (new RawPayload())->setIfNotEmpty('body', $params);
    }

    // --- where ---

    public function test_builder_where_query(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', '=', 'value');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.match'));
    }

    public function test_builder_where_default_operator_query(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'value');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.match'));
    }

    public function test_builder_where_not_equal(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', '<>', 'value');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must_not.0.match'));
    }

    // --- whereNot ---

    public function test_builder_where_not(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->whereNot('col', '=', 'value');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must_not.0.match'),
            'whereNot should place query in must_not'
        );
    }

    // --- whereIn ---

    public function test_builder_where_in(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->whereIn('col', ['a', 'b', 'c']);

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.terms'),
            'whereIn should produce a terms query'
        );
    }

    // --- whereMulti ---

    public function test_builder_where_multi(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->whereMulti(['field1', 'field2'], '=', 'search term');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.multi_match'),
            'whereMulti should produce a multi_match query'
        );
    }

    // --- range operators ---

    public function test_builder_where_gte(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('price', 'gte', 100);

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.range'));
    }

    public function test_builder_where_lte(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('price', 'lte', 100);

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.range'));
    }

    public function test_builder_where_between(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('price', 'between', [10, 100]);

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.range'));
    }

    // --- exists ---

    public function test_builder_where_exists(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'exists', true);

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.exists'));
    }

    // --- wildcard ---

    public function test_builder_where_wildcard(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'wildcard', '*pattern*');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.wildcard'));
    }

    public function test_builder_where_wildcard_not(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'wildcard_not', '*pattern*');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must_not.0.wildcard'),
            'wildcard_not should place query in must_not'
        );
    }

    // --- phrase ---

    public function test_builder_where_phrase(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'phrase', 'exact phrase');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.match_phrase'));
    }

    // --- begins_with / ends_with / contains ---

    public function test_builder_where_begins_with(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'begins_with', 'prefix');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.query_string'));
    }

    public function test_builder_where_ends_with(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'ends_with', 'suffix');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.query_string'));
    }

    public function test_builder_where_contains(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', 'contains', 'term');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.query.bool.must.0.query_string'));
    }

    // --- orWhere ---

    public function test_builder_or_where_closure(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->orWhere(function ($query, $boolean) {
            $query->where('col1', '=', 'value1', $boolean)
                  ->where('col2', '=', 'value2', $boolean);
            return $query;
        });

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.bool.should.0.match') &&
            $payload->has('body.query.bool.must.0.bool.should.1.match'),
            'orWhere closure should produce should clauses inside must'
        );
    }

    public function test_builder_or_where_chained_closures(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->orWhere(function ($query, $boolean) {
            $query->where('col', '=', 'value1', $boolean)
                  ->where('col', '=', 'value2', $boolean);
            return $query;
        })->orWhere(function ($query, $boolean) {
            $query->where('col', '=', 'value3', $boolean)
                  ->where('col', '=', 'value4', $boolean);
            return $query;
        });

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.bool.should.0.match') &&
            $payload->has('body.query.bool.must.0.bool.should.1.match') &&
            $payload->has('body.query.bool.must.1.bool.should.0.match') &&
            $payload->has('body.query.bool.must.1.bool.should.1.match'),
            'Chained orWhere closures should produce separate should blocks in must'
        );
    }

    // --- postFilter ---

    public function test_builder_post_filter(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->postFilter('status', '=', 'active');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.post_filter.bool.must.0.match'),
            'postFilter should add to post_filter body'
        );
    }

    public function test_builder_post_filter_not_equal(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->postFilter('status', '<>', 'deleted');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.post_filter.bool.must_not.0.match'),
            'postFilter with <> should add to must_not'
        );
    }

    // --- sort ---

    public function test_builder_sort(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->sort('name', 'asc', 'keyword');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.sort'));
    }

    public function test_builder_no_bool_query(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->sort('name', 'asc', 'myname');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.sort') && !$payload->has('body.query'));
    }

    // --- from / size / take ---

    public function test_builder_from_query(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->from(20);

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.from'));
    }

    public function test_builder_size_query(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->size(20);

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.size'));
    }

    public function test_builder_take(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->take(50);

        $payload = $this->buildPayload($builder);

        $params = json_decode(json_encode($builder->getQuery()), true);
        $this->assertEquals(50, $params['size']);
    }

    // --- aggs ---

    public function test_builder_aggs_query(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('col', '=', 'value')->rollup('col');

        $payload = $this->buildPayload($builder);

        $this->assertTrue($payload->has('body.aggs.agg_field.terms'));
    }

    // --- suggestions ---

    public function test_builder_suggestions(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->getSuggestions('test', 'name');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.suggest.suggestions.text') &&
            $payload->has('body.suggest.suggestions.term'),
            'getSuggestions should produce suggest block'
        );
    }

    public function test_builder_suggestions_multi_word_uses_keyword(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->getSuggestions('two words', 'name');

        $params = json_decode(json_encode($builder->getQuery()), true);

        $this->assertEquals(
            'name.keyword',
            $params['suggest']['suggestions']['term']['field'],
            'Multi-word suggestions should use keyword field'
        );
    }

    // --- _source ---

    public function test_builder_source(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->_source(['name', 'id']);

        $params = json_decode(json_encode($builder->getQuery()), true);

        $this->assertEquals(['name', 'id'], $params['_source']);
    }

    // --- combined queries ---

    public function test_builder_combined_where_and_sort(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('status', '=', 'active')
                ->sort('created_at', 'desc')
                ->from(0)
                ->take(10);

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.match') &&
            $payload->has('body.sort'),
            'Combined where + sort should have both'
        );
    }

    public function test_builder_multiple_wheres(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->where('status', '=', 'active')
                ->where('type', '=', 'premium');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.match') &&
            $payload->has('body.query.bool.must.1.match'),
            'Multiple wheres should stack in must array'
        );
    }

    // --- track_total_hits ---

    public function test_builder_track_total_hits(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $params = json_decode(json_encode($builder->getQuery()), true);

        $this->assertTrue($params['track_total_hits']);
    }

    // --- default size ---

    public function test_builder_default_size(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $params = json_decode(json_encode($builder->getQuery()), true);

        $this->assertEquals(25, $params['size']);
    }

    // --- wherePrefix ---

    public function test_builder_where_prefix(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->wherePrefix('name', 'tes');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.intervals'),
            'wherePrefix should produce intervals query'
        );
    }

    // --- whereFuzzy ---

    public function test_builder_where_fuzzy(): void
    {
        $builder = QueryBuilder::mockQueryBuilder();
        $builder->whereFuzzy('name', 'tset');

        $payload = $this->buildPayload($builder);

        $this->assertTrue(
            $payload->has('body.query.bool.must.0.intervals'),
            'whereFuzzy should produce intervals query'
        );
    }
}
