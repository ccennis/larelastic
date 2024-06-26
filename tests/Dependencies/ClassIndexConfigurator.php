<?php

namespace Larelastic\Elastic\Tests\Dependencies;

use Larelastic\Elastic\Models\IndexConfigurator as ElasticIndexConfigurator;
use Larelastic\Elastic\Models\IndexConfigurator;

class ClassIndexConfigurator extends IndexConfigurator
{
    /**
     * @param array $params Available parameters: name, settings, default_mapping, methods.
     * @return ElasticIndexConfigurator
     */
    public function mockIndexConfigurator(array $params = [])
    {
        $name = $params['name'] ?? 'test';
        $methods = array_merge($params['methods'] ?? [], [
            'getName',
            'getSettings',
            'getDefaultMapping',
            'getWriteAlias',
        ]);

        $mock = $this->getMockBuilder(ElasticIndexConfigurator::class)
            ->setMethods($methods)->getMock();
        $mock->method('getName')
            ->willReturn($name);
        $mock->method('getSettings')
            ->willReturn($params['settings'] ?? []);
        $mock->method('getDefaultMapping')
            ->willReturn($params['default_mapping'] ?? []);
        $mock->method('getWriteAlias')
            ->willReturn($name . '_write');
        return $mock;
    }
}