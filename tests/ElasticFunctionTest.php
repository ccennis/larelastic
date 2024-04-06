<?php


namespace Larelastic\Elastic\Tests;

use Larelastic\Elastic\ElasticEngine;
use Larelastic\Elastic\Payloads\RawPayload;
use Larelastic\Elastic\Tests\Dependencies\QueryBuilder;
use function json_decode;
use function json_encode;

class ElasticFunctionTest extends AbstractTestCase
{
    private $engine;
    protected function setUp()
    {
        $this->engine = $this
            ->getMockBuilder(ElasticEngine::class)
            ->disableOriginalConstructor()
            ->setMethods(array('wrapCriteria'))
            ->getMock();
    }

    //test standard where query
    public function test_builder_where_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->where('col', '=', 'value');

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())
            ->setIfNotEmpty('body', $params);

        $this->assertNotNull($payload->has('body.query.bool.must.0.match'));
    }

    //test that user can send no operator and default to `where`
    public function test_builder_where_default_operator_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->where('col', 'value');

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())
            ->setIfNotEmpty('body', $params);

        $this->assertTrue($payload->has('body.query.bool.must.0.match'));
    }

    //test that user can send a where this or that query
    public function test_builder_or_where_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->orWhere(['col', '=', 'value'], ['col', '=', 'value']);

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())->setIfNotEmpty('body', $params);

        $payload->has('body.query.bool.should.0.bool.must.0.0.match');

        //return an array of should -> must -> match instances. must has a negligible array but could wrap multiple in the case of "and wheres"
        $this->assertTrue($payload->has('body.query.bool.should.0.bool.must.0.0.match') &&
            $payload->has('body.query.bool.should.1.bool.must.0.0.match'),
            "Not all `or where` conditions are present");
    }

    //test that user can send a where (this and that) OR (this and that) query
    public function test_builder_callback_nested_where_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->orWhere(function($query, $boolean){
            $query->where('col', '=','value1', $boolean)->where('col', '=','value2', $boolean);
            return $query;
        })->orWhere(function($query, $boolean){
            $query->where('col', '=','value1', $boolean)->where('col', '=','value2', $boolean);
            return $query;
        });

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())->setIfNotEmpty('body', $params);

        $this->assertTrue($payload->has('body.query.bool.should.0.bool.must.0.0.match') &&
            $payload->has('body.query.bool.should.0.bool.must.0.1.match') &&
            $payload->has('body.query.bool.should.1.bool.must.0.0.match') &&
            $payload->has('body.query.bool.should.1.bool.must.0.1.match'),
            "Not all nested where conditions are present");
    }

    //test that query object is not added to body in the event of no wheres passed
    public function test_builder_no_bool_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->sort('name', 'asc', 'myname');

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())
            ->setIfNotEmpty('body', $params);

        $this->assertTrue($payload->has('body.sort') && !$payload->has('body.query'));
    }

    //test that from object is added to body
    public function test_builder_from_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->from(20);

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())
            ->setIfNotEmpty('body', $params);

        $this->assertTrue($payload->has('body.from'));
    }

    //test that size object is added to body
    public function test_builder_size_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->size(20);

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())
            ->setIfNotEmpty('body', $params);

        $this->assertTrue($payload->has('body.size'));
    }

    //test aggs query
    public function test_builder_aggs_query()
    {
        $builder = QueryBuilder::mockQueryBuilder();

        $builder->where('col', '=', 'value')->rollup('col');

        //convert builder stdObject to array
        $params = json_decode(json_encode($builder->getQuery()), true);

        $payload = (new RawPayload())
            ->setIfNotEmpty('body', $params);

        $this->assertTrue($payload->has('body.aggs.agg_field.terms'));
    }
}