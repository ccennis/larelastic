<?php

namespace ccennis\Larelastic\Contracts;


interface LarelasticInterface
{
    public function must($data);

    public function must_not($data);

    public function should($data);

    public function sort($data);

}