<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/11/19
 * Time: 4:08 PM
 */

namespace ccennis\Larelastic\Models;

class Elastic
{
    public $query;
    public $sort;
    public $from;
    public $size;

    /**
     * Elastic constructor.
     * @param $query
     * @param $sort
     * @param $from
     * @param $size
     */
    public function __construct()
    {
        $this->sort = [];
    }


    public function bool($bool)
    {
        $boolArray['bool'] = $bool;

        $this->query = $boolArray;

        return $this;
    }

    public function size($size = null)
    {
        $this->size = $size !== null ? $size : 25;

        return $this;
    }

    public function from($from = null)
    {
        $this->from = $from !== null ? $from : 0;

        return $this;
    }

    public function sort($sort = null)
    {
        $this->sort = $sort !== null ? $sort : [];

        return $this;
    }
}