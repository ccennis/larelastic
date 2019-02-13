<?php

namespace ccennis\Larelastic\Facades;

use Illuminate\Support\Facades\Facade;

class Elastic extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    protected static function getFacadeAccessor()
    {
        return 'ccennis.larelastic';
    }
}