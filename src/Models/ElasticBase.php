<?php

namespace ccennis\larelastic\Models;


class ElasticBase
{
    public $query;

    /**
     * ElasticQuery constructor.
     * @param $query
     */

    public function term($data)
    {
        $termArray['term'] = [$data['field'] => $data['value']];

        $this->query = $termArray;

        return $this;
    }

    public function bool($bool)
    {
        $boolArray['bool'] = $bool == null ? new stdClass() : $bool;

        $this->query = $boolArray;

        return $this;
    }
}