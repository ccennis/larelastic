<?php


namespace Larelastic\Elastic;


use Larelastic\Elastic\Indexers\IndexerInterface;
use Larelastic\Elastic\Payloads\IndexPayload;
use Larelastic\Elastic\Services\NestedQueryService;
use Larelastic\Elastic\Services\QueryService;
use Larelastic\Elastic\Facades\Elastic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Log;
use function json_encode;

class ElasticEngine extends Engine
{
    /**
     * ElasticEngine constructor.
     *
     * @param \Larelastic\Elastic\Indexers\IndexerInterface $indexer
     * @param bool $updateMapping
     * @return void
     */
    public function __construct(IndexerInterface $indexer, $updateMapping)
    {
        $this->indexer = $indexer;

        $this->updateMapping = $updateMapping;
    }

    /**
     * The indexer interface.
     *
     * @var \Larelastic\Elastic\Indexers\IndexerInterface
     */
    protected $indexer;

    /**
     * Should the mapping be updated.
     *
     * @var bool
     */
    protected $updateMapping;

    /**
     * The updated mapping.
     *
     * @var array
     */
    protected static $updatedMapping = [];

    /**
     * {@inheritdoc}
     */
    public function update($models)
    {
        if ($this->updateMapping) {
            $self = $this;

            $models->each(function ($model) use ($self) {
                $modelClass = get_class($model);

                if (in_array($modelClass, $self::$updatedMapping)) {
                    return true;
                }

                Artisan::call(
                    'elastic:update-mapping',
                    ['model' => $modelClass]
                );

                $self::$updatedMapping[] = $modelClass;
            });
        }

        $this->indexer
            ->update($models);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($models)
    {
        $this->indexer->delete($models);
    }


    /**
     * Perform the search.
     *
     * @param \Laravel\Scout\Builder $builder
     * @param array $options
     * @return array|mixed
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $params = json_decode(json_encode($builder->getQuery()),true);

        //debug log out the actual query being run on elastic
        Log::debug(json_encode($builder->getQuery()));

        return $this->searchRaw($builder->model, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function search(Builder $builder)
    {
        return $this->performSearch($builder);
    }

    /**
     * Make a raw search.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $query
     * @return mixed
     */
    public function searchRaw(Model $model, $query)
    {
        $payload = (new IndexPayload($model->getIndexConfigurator()))
            ->setIfNotEmpty('body', $query)
            ->get();

        return Elastic::search($payload);
    }

    public function where($col, $operator, $value, $boolean = 'and', $nested = false)
    {
        $service = $nested ? NestedQueryService::class : QueryService::class;
        $this->boolType = $operator == '<>' ? 'must_not' : 'must';

        if ($boolean == 'or') {

            $this->orWheres[] = [$col, $operator, $value];

        } else {
            $this->bool[$this->boolType][] = $service::buildQuery($this->wrapCriteria([$col, $operator, $value]));
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $builder
            ->from((max($page,1) - 1) * $perPage)
            ->take($perPage);

        return $this->performSearch($builder);
    }

    /**
     * Explain the search.
     *
     * @param \Laravel\Scout\Builder $builder
     * @return array|mixed
     */
    public function explain(Builder $builder)
    {
        return $this->performSearch($builder, [
            'explain' => true,
        ]);
    }

    /**
     * Profile the search.
     *
     * @param \Laravel\Scout\Builder $builder
     * @return array|mixed
     */
    public function profile(Builder $builder)
    {
        return $this->performSearch($builder, [
            'profile' => true,
        ]);
    }

    /**
     * Return the number of documents found.
     *
     * @param \Laravel\Scout\Builder $builder
     * @return int
     */
    public function count(Builder $builder)
    {
        return $this->getTotalCount($this->performSearch($builder));
    }

    /**
     * {@inheritdoc}
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id');
    }

    /**
     * {@inheritdoc}
     */
    public function map(Builder $builder, $results, $model)
    {
        //get the source object off each result set
        $data['response'] = collect($results['hits']['hits'])->map(function ($item){
            return $item['_source'];
        });

        if (isset($results["aggregations"])) {
            $data['aggs'] = $results["aggregations"] ?? null;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount($results)
    {
        //post 7 versions have total as an array
        return $results['hits']['total']['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function flush($model)
    {
        $query = $model::usesSoftDelete() ? $model->withTrashed() : $model->newQuery();

        $query
            ->orderBy($model->getScoutKeyName())
            ->unsearchable();
    }

    //these are brought in from the Engine class now required for this version.
    //TODO implement these methods
    public function lazyMap(Builder $builder, $results, $model)
    {
        // TODO: Implement lazyMap() method.
    }

    public function createIndex($name, array $options = [])
    {
        //
    }

    public function deleteIndex($name)
    {
        //
    }

}
