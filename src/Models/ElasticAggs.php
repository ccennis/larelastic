<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 5/3/19
 * Time: 10:38 AM
 */

namespace ccennis\larelastic\Models;


class ElasticAggs extends ElasticQuery {

    public $aggs;


    public function aggs($aggs)
    {
        $this->aggs = $aggs ?? [];

        return $this;
    }
}