<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/11/19
 * Time: 8:06 AM
 */

namespace ccennis\Larelastic\Services;


use ccennis\Larelastic\Models\Elastic;
use GuzzleHttp\Client;
use ccennis\Larelastic\Contracts\LarelasticInterface;

class ElasticService  implements LarelasticInterface
{
    public $must;
    public $must_not;
    public $sort;
    public $bool;
    public $query;
    public $size;
    public $from;

    /**
     * ElasticService constructor.
     */
    public function __construct()
    {
        //todo add connection here
        $this->sort = [];
        $this->url = env('ELASTIC_URL');
    }

    //simple query select -- get where equals equivalent
    public function get($data){

        $this->query['term'] = [
            $data['field'] => $data['value']
        ];

        return $this;
    }

    public function must($data)
    {
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

    public function query(){

        $client = new Client();

        $params = $this->getQuery();

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