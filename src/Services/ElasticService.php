<?php

namespace ccennis\Larelastic\Services;

use ccennis\Larelastic\Models\ElasticQuery;
use GuzzleHttp\Client;
use Config;

class ElasticService
{
    public $_source;
    public $sort;
    public $bool;
    public $query;
    public $size;
    public $from;
    public $page;
    public $url;
    public $term;

    /**
     * ElasticService constructor.
     */
    public function __construct()
    {
        //todo add connection here
        $this->sort = [];
        $this->base_url = Config::get('larelastic.default.base_url');
        $this->url = $this->base_url."/".Config::get('larelastic.default.index')."/_search";
    }

    //simple query select -- get where equals equivalent
    public function get($data)
    {
        $elasticQuery = new ElasticQuery();

        $elasticQuery->_source($this->_source);
        $elasticQuery->term($data);
        $elasticQuery->sort($this->sort);
        $elasticQuery->size($this->size);
        $elasticQuery->from($this->from, $this->page);

        return $this->query(json_encode($elasticQuery));

    }

    public function index($index)
    {
        if ($index) {
            $this->url = $this->base_url.= "/" . $index . "/_search";
        }
        return $this;
    }

    public function term($data){

        $termArray['term'] = [$data['field'] => $data['value']];

        $this->query = $termArray;

        return $this;
    }

    public function must($data)
    {
        //must needs to be a multi-dimensional array but we can accept just the search_criteria and wrap it
        $data = count($data) == count($data, COUNT_RECURSIVE) ? [$data] : $data;

        foreach ($data as $searchItem) {
            //make sure this isn't an empty array
            if (count($searchItem) > 0) {
                if (isset($searchItem['nested']) && $searchItem['nested'] == true) {
                    $this->bool['must'][] = NestedQueryService::buildQuery($searchItem);
                } else {
                    $this->bool['must'][] = QueryService::buildQuery($searchItem);
                }
            }
        }
        return $this;
    }

    public function must_not($data)
    {
        //must_not needs to be a multi-dimensional array but we can accept just the search_criteria and wrap it
        $data = count($data) == count($data, COUNT_RECURSIVE) ? [$data] : $data;

        foreach ($data as $searchItem) {
            if (count($searchItem) > 0) {
                $this->bool['must_not'][] = QueryService::buildQuery($searchItem);
            }
        }
        return $this;
    }

    public function filter($data)
    {
        if (isset($data['clauses'])) {

            $this->bool['filter'] = QueryService::buildFilterQuery($data);
        }
        return $this;
    }

    public function should($data)
    {
        if (isset($data['clauses'])) {

            $this->bool['must'][] = QueryService::buildShouldQuery($data);
        }
        return $this;
    }

    public function sort($data)
    {
        if (isset($data['nested']) && $data['nested'] == true) {
            $this->sort = NestedQueryService::buildSort($data);
        } else {
            $this->sort = QueryService::buildSort($data);
        }
        return $this;
    }

    public function size($data){

        $this->size = $data;

        return $this;
    }

    public function page($data){

        $this->page = $data;

        return $this;
    }

    public function _source($data){

        $this->_source = $data;

        return $this;
    }

    public function getSort()
    {
        if(isset($this->sort)) {
            return [
                'sort' => $this->sort
            ];
        }
    }

    public function query($params = null){

        $client = new Client();

        $body = $params == null ? $this->getQuery() : $params;

        $result = $client->request('POST', $this->url, [
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
            'body' => $body
        ]);

        return json_decode($result->getBody()->getContents());
    }

    public function getQuery(){

        $elasticQuery = new ElasticQuery();

        $elasticQuery->_source($this->_source);
        $elasticQuery->bool($this->bool);
        $elasticQuery->sort($this->sort);
        $elasticQuery->size($this->size);
        $elasticQuery->from($this->from, $this->page);

        return json_encode($elasticQuery);
    }
}