<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 5/3/19
 * Time: 10:38 AM
 */

namespace ccennis\Larelastic\Models;


class ElasticAggs extends ElasticQuery {

    public $aggs;


    public function aggs($aggs)
    {
        $this->aggs = $aggs ?? [];

        return $this;
    }
}