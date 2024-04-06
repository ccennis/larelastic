<?php

namespace Larelastic\Elastic\Facades;

use Illuminate\Support\Facades\Facade;

class Elastic extends Facade
{
    /**
     * Get the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'larelastic.elastic';
    }
}
