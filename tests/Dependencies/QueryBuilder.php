<?php

namespace Larelastic\Elastic\Tests\Dependencies;

class QueryBuilder
{
    public static function mockQueryBuilder()
    {
        $builder = new \Larelastic\Elastic\Builders\QueryBuilder(
            new TestModel(),
            ''
        );

        return $builder;
    }
}
