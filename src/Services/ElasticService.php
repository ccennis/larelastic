<?php

namespace ccennis\Larelastic\Services;

use ccennis\Larelastic\Models\Elastic;
use GuzzleHttp\Client;
use Config;

class ElasticService
{
    public $must;
    public $must_not;
    public $sort;
    public $bool;
    public $query;
    public $size;
    public $from;
    public $url;

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
        $params['query']['term'] = [
            $data['field'] => $data['value']
        ];

        return $this->query(json_encode($params));
    }

    public function index($index)
    {
        if ($index) {
            $this->url = $this->base_url.= "/" . $index . "/_search";
        }
        return $this;
    }

    public function must($data)
    {
        //must needs to be a multi-dimensional array but we can accept just the search_criteria and wrap it
        $data = count($data) == count($data, COUNT_RECURSIVE) ? [$data] : $data;

        foreach ($data as $searchItem) {
            if ($searchItem['nested'] == true) {
                $this->bool['must'][] = NestedQueryService::buildQuery($searchItem);
            } else {
                $this->bool['must'][] = QueryService::buildQuery($searchItem);
            }
        }
        return $this;
    }

    public function must_not($data)
    {
        //must_not needs to be a multi-dimensional array but we can accept just the search_criteria and wrap it
        $data = count($data) == count($data, COUNT_RECURSIVE) ? [$data] : $data;

        foreach ($data as $searchItem) {

            $this->bool['must_not'][] = QueryService::buildQuery($searchItem);
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
        if ($data['nested'] == true) {
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

        $params = null ? $this->getQuery() : $params;

        $result = $client->request('POST', $this->url, [
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
            'body' => $params
        ]);

        return json_decode($result->getBody()->getContents());
    }

    public function getQuery(){

        $query = new Elastic();

        $query->bool($this->bool);
        $query->from($this->from);
        $query->sort($this->sort);
        $query->size($this->size);

        return json_encode($query);
    }
}