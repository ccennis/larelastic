<?php

namespace ccennis\Larelastic\Models;

class Elastic
{
    public $query;
    public $sort;
    public $from;
    public $size;
    private $page;

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

    private function page($page = null)
    {
        $this->page = $page !== null ? $page : 1;

        return $this;
    }

    public function size($size = null)
    {
        $this->size = $size !== null ? $size : 25;

        return $this;
    }

    public function from($from = null, $page = null)
    {
        $this->from = $page == 1 ? 0 : (($page - 1) * $this->size);

        return $this;
    }

    public function sort($sort = null)
    {
        $this->sort = $sort !== null ? $sort : [];

        return $this;
    }
}