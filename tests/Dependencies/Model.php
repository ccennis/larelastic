<?php


namespace Larelastic\Elastic\Tests\Dependencies;

use Larelastic\Elastic\Traits\Searchable;
use Larelastic\Tests\Stubs\Model as StubModel;
use Larelastic\Elastic\Tests\Dependencies\ClassIndexConfigurator as MockIndexConfigurator;

class Model extends \Illuminate\Database\Eloquent\Model
{
   // use IndexConfigurator;
    use Searchable;
   // use QueryBuilder;

    protected $indexConfigurator = MockIndexConfigurator::class;

    /**
     * @param array $params Available parameters: search, index_configurator, methods.
     * @return Searchable
     */
    public function mockModel(array $params = [])
    {
        $methods = array_merge(
            $params['methods'] ?? [],
            [
                //'search',
                'getIndexConfigurator',
            ]
        );

        $mock = $this
            ->getMockBuilder(StubModel::class)
            ->setMethods($methods)
            ->getMock();

//        $mock
//            ->method('search')
//            ->willReturn($this->mockQueryBuilder());

        $mock
            ->method('getIndexConfigurator')
            ->willReturn($params['index_configurator'] ?? $this->mockIndexConfigurator());

        return $mock;
    }
}