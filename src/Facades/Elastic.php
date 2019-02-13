<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/12/19
 * Time: 3:48 PM
 */

namespace cennis\ccennis\Larelastic\Facades;

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