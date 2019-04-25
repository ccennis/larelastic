<?php

namespace ccennis\Larelastic\Services;

use HAN\App\Elastic\Models\ElasticCount;
use HAN\App\Elastic\Models\ElasticQuery;
use GuzzleHttp\Client;
use Config;
use function str_replace;

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
        $this->base_url = Config::get('search.default.base_url');
        $this->url = $this->base_url."/".Config::get('search.default.index');
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

    public function id($id, $type){

        $client = new Client();

        $result = $client->request('GET', $this->url."/".$type."/".$id, [
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
        ]);

        $this->destroy();
        $this->__construct();

        return json_decode($result->getBody()->getContents());
    }

    public function index($index)
    {
        $this->base_url = $this->resetBaseUrl();

        if ($index) {
            $this->url = $this->base_url.= "/" . $index;
        }
        return $this;
    }

    public function resetBaseUrl(){

        return Config::get('search.default.base_url');
    }

    public function term($data){

        $termArray['term'] = [$data['field'] => $data['value']];

        $this->query = $termArray;

        return $this;
    }

    public function multimatch($data, $type = 'cross_fields')
    {
        foreach ($data as $searchItem) {
            //make sure this isn't an empty array
            if (count($searchItem) > 0) {
                $this->bool['must'][]['multi_match'] = [
                    'type' => $type,
                    'query' => $searchItem['value'],
                    'fields' => $searchItem['field'],
                    'operator' => 'and',
                    'analyzer' => 'standard'
                ];
            }
        }
        return $this;
    }

    public function must($data)
    {
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
        foreach ($data as $searchItem) {
            if (count($searchItem) > 0) {
                if (isset($searchItem['nested']) && $searchItem['nested'] == true) {
                    $this->bool['must_not'][] = NestedQueryService::buildQuery($searchItem);
                } else {
                    $this->bool['must_not'][] = QueryService::buildQuery($searchItem);
                }
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
            foreach($data['clauses'] as $clause) {
                if (isset($clause['nested']) && $clause['nested'] == true) {
                    $shouldArray[] = NestedQueryService::buildQuery($clause);
                } else {
                    $shouldArray[] = QueryService::buildQuery($clause);
                }
            }
        }

        $this->bool['must'][] = [
            'bool' => [
                'should' =>
                    $shouldArray,
                'minimum_should_match' => 1
            ],
        ];

        return $this;
    }

    public function sort($data)
    {
        foreach($data as $sort) {
            if (isset($sort['nested']) && $sort['nested'] == true) {
                $this->sort[] = NestedQueryService::buildSort($sort);
            } else {
                $this->sort[] = QueryService::buildSort($sort);
            }
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

        $result = $client->request('POST', $this->url. "/_search", [
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
            'body' => $body
        ]);

        $this->destroy();
        $this->__construct();

        return json_decode($result->getBody()->getContents());
    }

    public function count($params = null){

        $client = new Client();

        $body = $params == null ? $this->getCount() : $params;

        $result = $client->request('POST', $this->url. "/_count", [
            'headers' => ['content-type' => 'application/json', 'Accept' => 'application/json'],
            'body' => $body
        ]);

        $this->destroy();
        $this->__construct();

        return json_decode($result->getBody()->getContents())->count;
    }

    public function getQuery()
    {
        $elasticQuery = new ElasticQuery();

        $elasticQuery->_source($this->_source);
        $elasticQuery->bool($this->bool);
        $elasticQuery->sort($this->sort);
        $elasticQuery->size($this->size);
        $elasticQuery->from($this->from, $this->page);

        return json_encode($elasticQuery);
    }

    public function getCount(){

        $elasticCount = new ElasticCount();

        $elasticCount->bool($this->bool);

        return json_encode($elasticCount);
    }

    private function destroy() {
        foreach ($this as $key => $value) {
            $this->$key = null;
        }
    }
}