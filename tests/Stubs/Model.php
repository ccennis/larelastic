<?php

namespace Larelastic\Tests\Stubs;

use Larelastic\Elastic\Traits\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Model extends \Illuminate\Database\Eloquent\Model
{
    use Searchable, SoftDeletes;

    protected $name = 'products';
}
