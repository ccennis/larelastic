<?php


namespace Larelastic\Elastic\Tests\Dependencies;


use Larelastic\Elastic\ElasticEngine;
use Larelastic\Elastic\Indexers\SingleIndexer;
use Elasticsearch\Client;
use Mockery;

class QueryBuilder
{
    public static function mockQueryBuilder()
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('search')->with('modified_by_callback');
        $engine = new ElasticEngine(new SingleIndexer(), true);
        $builder = new \Larelastic\Elastic\Builders\QueryBuilder(
            new Model(),
            '',
            function (Client $client, $query, $params) {
                $params = ['modified_by_callback'];
                return $client->search($params);
            }
        );

        return $builder;
    }
}