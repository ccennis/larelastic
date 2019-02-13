<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/9/19
 * Time: 2:37 PM
 */

namespace ccennis\Larelastic\Contracts;


interface LarelasticInterface
{
    public function must($data);

    public function must_not($data);

    public function should($data);

    public function sort($data);

}