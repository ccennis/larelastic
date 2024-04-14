<?php


namespace Larelastic\Elastic\Builders;

use Larelastic\Elastic\Constants\ElasticSettings;
use Larelastic\Elastic\Models\ElasticQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Laravel\Scout\Builder;
use Larelastic\Elastic\Services\NestedQueryService;
use Larelastic\Elastic\Services\QueryService;
use Closure;
use Config;
use Log;
use function array_pop;
use function count;
use function explode;
use function implode;
use function func_get_args;
use function array_combine;
use function array_splice;
use function array_intersect;
use function request;

class QueryBuilder extends Builder
{

    /**
     * The _source object.
     *
     * @var array|string
     */
    public $_source;

    /**
     * The sort object.
     *
     * @var array|string
     */
    public $sort;

    /**
     * The bool object -- determines logical groupings.
     *
     * @var array|string
     */
    public $bool;

    /**
     * The query object.
     *
     * @var array|string
     */
    public $query;

    /**
     *
     * @var array|integer
     */
    public $size;

    /**
     *
     * @var array|integer
     */
    public $take;

    /**
     *
     * @var array|integer
     */
    public $page;

    /**
     *
     * @var array|string -- 'must' or 'must_not'
     */
    public $boolType;

    /**
     * "Or" statements array (user input).
     *
     * @var array
     */
    public $orWheres = [];

    /**
     * "And" statements array (user input).
     *
     * @var array
     */
    public $andWheres = [];

    /**
     * The with array.
     *
     * @var array|string
     */
    public $with;

    /**
     * The offset.
     *
     * @var int
     */
    public $offset;

    /**
     * The collapse parameter.
     *
     * @var string
     */
    public $collapse;

    /**
     * The rollup parameter.
     *
     * @var string
     */
    public $rollup;

    /**
     * The rollup parameter.
     *
     * @var array
     */
    public $aggs;

    /**
     * The post_filter parameter.
     *
     * @var array
     */
    public $postFilter;

    /**
     * The select array.
     *
     * @var array
     */
    public $select = [];

    public function _source($data)
    {
        $this->_source = $data;

        return $this;
    }

    //allows for complex and simple wheres, nested or otherwise according to bool operator
    public function where($col, $operator, $value = null, $boolean = null)
    {
        $nestPath = $this->getNestPath($col);
        $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;
        $this->boolType = in_array($operator, ['<>', 'wildcard_not']) ? 'must_not' : 'must';

        if ($boolean == 'or') {

            $this->orWheres[] = $service::buildQuery($this->wrapCriteria(func_get_args()));

        } else if ($boolean == 'and') {
            $this->andWheres[] = $service::buildQuery($this->wrapCriteria(func_get_args()));

        } else {
            //getting args in the event only col and value are sent -- count should represent 2
            $this->bool[$this->boolType][] = $service::buildQuery($this->wrapCriteria(func_get_args()));
        }

        return $this;
    }

    //allows for greater usage of operators under the must_not block
    public function whereNot($col, $operator, $value = null)
    {
        $nestPath = $this->getNestPath($col);
        $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;
        $this->boolType = 'must_not';

        //getting args in the event only col and value are sent -- count should represent 2
        $this->bool[$this->boolType][] = $service::buildQuery($this->wrapCriteria(func_get_args()));

        return $this;
    }

    //this is to build a TOP LEVEL compound query, i.e. THIS or THAT with multiple clauses
    public function nestedOr($shouldClauses)
    {
        $service = QueryService::class;

        foreach ($shouldClauses as $clauses) {
            $result[] = [
                'bool' => [
                    'minimum_should_match' => count($clauses),
                    'should' => $clauses
                ]
            ];
        }

        $this->bool['must'][]['bool']['should'] = $result;

        return $this;
    }

    public function orWhere($clauses, $minMatch = 1)
    {
        $args = func_get_args();
        $shouldClauses = [];
        $parentBlock = false;
        $andShouldBlock = null;
        $orShouldBlock = null;
        $this->boolType = 'must';

        //if the user passes a closure, we will wrap each where together and consider those wheres "ands"
        if ($args[0] instanceof Closure) {

            $this->wrapWheres($args[0]);

            if (count($this->andWheres) > 0 && count($this->orWheres) > 0) {
                $parentBlock = true;
            }

            if (count($this->andWheres) > 0) {
                $andShouldBlock = null;

                $shouldClauses = $this->andWheres;

                $andShouldBlock = QueryService::buildShould($shouldClauses, count($this->andWheres));

                $this->andWheres = [];
            }

            if (count($this->orWheres) > 0) {
                $orShouldBlock = null;

                $shouldClauses = $this->orWheres;
                $orShouldBlock = QueryService::buildShould($shouldClauses, $minMatch);
                $this->orWheres = [];
            }

            if ($parentBlock) {
                $parentShould = QueryService::buildShould([$andShouldBlock, $orShouldBlock], $minMatch);
                $this->bool[$this->boolType][] = $parentShould;
            } else {
                if($andShouldBlock || $orShouldBlock) {
                    $this->bool[$this->boolType][] = $andShouldBlock ?: $orShouldBlock;
                }
            }

        } else {
            if ($clauses instanceof QueryBuilder) {
                $shouldClauses = $clauses->bool['must'] ?? [];
            } else {
                foreach ($args as $clause) {
                    if (is_array($clause)) {
                        //getting arg clauses via closure (array of arrays):
                        if ($this->array_depth($clause) > 1) {
                            foreach ($clause as $andWhere) {
                                if (isset($andWhere[0])) {
                                    $nestPath = $this->getNestPath($andWhere[0]);
                                    $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;
                                    $musts[] = $service::buildQuery($this->wrapCriteria($andWhere));
                                }
                            }
                            $shouldClauses = $musts;
                            $musts = [];
                            //getting arg clauses as user supplied arrays
                        } else {
                            if (isset($clause[0])) {
                                $nestPath = $this->getNestPath($clause[0]);
                                $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;
                                $musts = $service::buildQuery($this->wrapCriteria($clause));
                                $shouldClauses = $musts;
                                $musts = [];
                            }
                        }
                    }
                }
            }

            $shouldBlock = QueryService::buildShould($shouldClauses, $minMatch);
            $this->bool[$this->boolType][] = $shouldBlock;
        }
        return $this;
    }

    //return only the query syntax without attaching it to current $this object
    public function whereSyntax($col, $operator, $value = null, $boolean = null)
    {
        $service = QueryService::class;
        $response = [];

        $boolType = in_array($operator, ['<>', 'wildcard_not']) ? 'must_not' : 'must';

        return $service::buildQuery($this->wrapCriteria(func_get_args()));
    }

    //return only the query syntax without attaching it to current $this object
    public function orWhereSyntax($clauses, $minMatch = 1)
    {
        $args = func_get_args();

        foreach ($args as $clause) {
            if (is_array($clause)) {
                //getting arg clauses via closure (array of arrays):
                if ($this->array_depth($clause) > 1) {
                    foreach ($clause as $andWhere) {
                        $musts[] = QueryService::buildQuery($this->wrapCriteria($andWhere));
                    }
                    $shouldClauses = $musts;
                    $musts = [];
                    //getting arg clauses as user supplied arrays
                } else {
                    $musts = QueryService::buildQuery($this->wrapCriteria($clause));
                    $shouldClauses = $musts;
                    $musts = [];
                }
            }
        }

        return $shouldClauses;
    }

    //match fronts of words, max ngrams defined in mapping 
    public function wherePrefix($field, $phrases, $type = 'all_of', $ordered = false, $filter = null)
    {
        $phrases = is_array($phrases) ? $phrases : [$phrases];
        $intervals = [];
        $query = null;

        foreach ($phrases as $phrase) {
            $intervals[] = [
                "prefix" => [
                    "prefix" => $phrase
                ]
            ];
        }

        if ($ordered) {
            $query = [
                "intervals" => [
                    $field => [
                        $type => [
                            "ordered" => true,
                            "intervals" => $intervals
                        ]
                    ]
                ]
            ];
        } else {
            $query = [
                "intervals" => [
                    $field => [
                        $type => [
                            'intervals' => $intervals
                        ]
                    ]
                ]
            ];
        }

        $this->bool['must'][] = $query;

        return $this;
    }

    //allows for partial matching, currently defaulted to "auto"
    public function whereFuzzy($field, $phrases, $type = 'all_of', $ordered = false, $fuzziness = 'AUTO')
    {
        $phrases = is_array($phrases) ? $phrases : [$phrases];
        $intervals = [];
        $query = null;

        foreach ($phrases as $phrase) {
            $intervals[] = [
                'fuzzy' => [
                    'term' => $phrase,
                    'fuzziness' => $fuzziness
                ]
            ];
        }

        if ($ordered) {
            $query = [
                'intervals' => [
                    $field => [
                        $type => [
                            'ordered' => true,
                            'intervals' => $intervals
                        ]
                    ]
                ]
            ];
        } else {
            $query = [
                'intervals' => [
                    $field => [
                        $type => [
                            'intervals' => $intervals
                        ]
                    ]
                ]
            ];
        }

        $this->bool['must'][] = $query;

        return $this;
    }


    //match full words ordered or not, filtering out phrases we don't want
    public function whereText($field, $phrases, $ordered = true, $filter = null)
    {
        $phrases = is_array($phrases) ? $phrases : [$phrases];

        foreach ($phrases as $phrase) {
            if ($ordered) {
                $intervals[] = [
                    'match' => [
                        'query' => $phrase,
                        'ordered' => $ordered,
                        'max_gaps' => 0,
                        'filter' => [
                            'not_containing' => [
                                'match' => [
                                    'query' => $filter ?? ''
                                ]
                            ]
                        ]
                    ]
                ];
            } else {
                $intervals[] = [
                    'match' => [
                        'query' => $phrase,
                        'filter' => [
                            'not_containing' => [
                                'match' => [
                                    'query' => $filter ?? ''
                                ]
                            ]
                        ]
                    ]
                ];
            }
        }

        $intervals = [
            'intervals' => [
                $field => [
                    'all_of' => [
                        'ordered' => $ordered,
                        'intervals' => $intervals
                    ]
                ]
            ]
        ];

        $this->bool['must'][] = $intervals;

        return $this;
    }

    public function whereIn($col, $array)
    {
        $nestPath = $this->getNestPath($col);
        $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;

        $this->bool['must'][] = $service::buildQuery($this->wrapCriteria([$col, 'in', $array]));

        return $this;
    }

    public function whereMulti(Array $col, $operator, $value, $type = 'phrase_prefix')
    {
        $this->bool['must'][]['multi_match'] = [
            'type' => $type,
            'query' => $value,
            'fields' => $col,
            'operator' => 'and',
            'analyzer' => 'standard'
        ];
        return $this;
    }


    public function geoSort($data)
    {
        $nestPath = $this->getNestPath($data['field']);
        $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;

        $this->sort[] = $service::buildGeoSort($data);

        return $this;
    }


    public function sort($field, $order = null, $fieldType = null)
    {
        $data = [
            'field' => $field,
            'order' => $order,
            'field_type' => $fieldType
        ];

        $nestPath = $this->getNestPath($field);
        $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;

        $this->sort[] = $service::buildSort($data);

        return $this;
    }

    public function size($data)
    {
        $this->size = $data;

        return $this;
    }

    public function take($data)
    {
        $this->take = $data;

        return $this;
    }

    public function page($data)
    {
        $this->page = $data;

        return $this;
    }

    public function from($offset)
    {
        $this->offset = $offset;

        return $this;
    }

    public function rollup($field)
    {
        $data = [
            'field' => $field,
        ];

        $service = QueryService::class;

        $this->aggs = $service::buildAgg($data);

        return $this;
    }

    public function postFilter($field, $operator, $value)
    {
        $nestPath = $this->getNestPath($field);
        $service = $nestPath !== null ? NestedQueryService::class : QueryService::class;

        $boolType = in_array($operator, ['<>', 'wildcard_not']) ? 'must_not' : 'must';

        $this->postFilter['bool'][$boolType][] = $service::buildQuery($this->wrapCriteria(func_get_args()));

        return $this;
    }

    public function getSuggestions($searchTerm, $field)
    {
        if (str_word_count($searchTerm) > 1) {
            $field .= '.keyword';
        }

        $suggestions = [
            "suggestions" =>
                [
                    "text" => $searchTerm,
                    "term" => [
                        "field" => $field
                    ]
                ]
        ];

        $this->suggest = $suggestions;

        return $this;
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param int $perPage
     * @param string $pageName
     * @param int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = null, $pageName = 'page', $page = null)
    {
        $engine = $this->engine();

        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $results = $this->model->newCollection(
            $engine->map(
                $this, $rawResults = $engine->paginate($this, $perPage, $page), $this->model
            )
        );

        $paginator = (new LengthAwarePaginator($results, min($engine->getTotalCount($rawResults), ElasticSettings::MAX_RESULT_SET), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]));

        //update all links to use query params and append page=#
        if (!request()->has('page')) {
            request()->request->add(['page' => 1]);
        }
        $paginator->appends(request()->except('page'))->links();

        if(isset($rawResults['suggest'])){ //the user got no results, show them what we searched instead
            $paginator->suggest = $rawResults['suggest'];
        }

        return $paginator;
    }

    //build the query object based on the params set in Builder
    public function getQuery()
    {
        $elasticQuery = new ElasticQuery($this);

        return $elasticQuery;
    }

    /**
     * Get the count of documents of the search.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function count()
    {
        return $this->engine()->count($this);
    }

    private function wrapWheres(Closure $closure)
    {
        $closure($this, 'or');
    }

    //manipulate the search string to feed it to QueryService
    private function wrapCriteria($data)
    {
        if (is_array($data)) {
            //if length of array is 2, operator default is "="
            if (count($data) == 2) {

                //keep the operator position in the center of this array
                array_splice($data, 1, 0, '=');
            }

            //slicing $data on the offchance boolean is passed, which we don't need
            $criteriaArray = array_combine(['field', 'operator', 'value'], array_slice($data, 0, 3));
            $this->boolType = $criteriaArray['operator'] == '<>' ? 'must_not' : 'must';

            return $criteriaArray;
        }
    }

    private function getNestPath($field)
    {
        {
            $arr = explode(".", $field);
            if (count($arr) > 1) {
                if (empty(array_intersect(['raw', 'keyword', 'quoted'], $arr))) {
                    array_pop($arr);
                    return implode(".", $arr);
                }
            }
            return null;
        }
    }

    private function array_depth($array)
    {
        $max_indentation = 1;

        $array_str = print_r($array, true);
        $lines = explode("\n", $array_str);

        foreach ($lines as $line) {
            $indentation = (strlen($line) - strlen(ltrim($line))) / 4;

            if ($indentation > $max_indentation) {
                $max_indentation = $indentation;
            }
        }

        return (int)ceil(($max_indentation - 1) / 2) + 1;
    }
}
