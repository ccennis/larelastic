<?php

namespace Larelastic\Elastic\Models;

use Laravel\Scout\Builder;

class ElasticQuery
{
    public $sort;
    public $from;
    public $size;
    public $_source;
    public $track_total_hits;
    public $query;
    public $aggs;
    public $post_filter;
    public $suggest;
    public $page;

    /**
     * ElasticQuery constructor.
     * @param $query
     * @param $sort
     * @param $from
     * @param $size
     * @param $aggs
     */
    public function __construct(Builder $builder)
    {
        $this->_source = $builder->_source ?? [];
        $this->sort = $builder->sort ?: [];
        $this->size = $builder->take ?: 25;
        $this->from = $builder->offset ?: 0;

        $this->track_total_hits = true;

        if($builder->bool){
            $this->query = ['bool' => $builder->bool];
        }

        if($builder->aggs){
            $this->aggs = $builder->aggs;
        }

        if ($builder->postFilter) {
            $this->post_filter = $builder->postFilter;
        }

        if(isset($builder->suggest)){
            $this->suggest = $builder->suggest;
        }
    }

    /**
     * @return mixed
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @param mixed $page
     */
    public function setPage($page): void
    {
        $this->page = $page;
    }

    /**
     * @return mixed
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @param mixed $source
     */
    public function setSource($source): void
    {
        $this->_source = $source;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from): void
    {
        $this->from = $from;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }
}