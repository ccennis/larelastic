<?php


namespace Larelastic\Elastic\Traits;

use Larelastic\Elastic\Builders\QueryBuilder;
use Exception;
use Laravel\Scout\Searchable as SourceSearchable;
use Log;
use function sprintf;

trait Searchable
{
    use SourceSearchable {
        SourceSearchable::bootSearchable as sourceBootSearchable;
        SourceSearchable::getScoutKeyName as sourceGetScoutKeyName;
    }

    /**
     * Defines if the model is searchable.
     *
     * @var bool
     */
    protected static $isSearchableTraitBooted = false;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootSearchable()
    {
        if (static::$isSearchableTraitBooted) {
            return;
        }

        self::sourceBootSearchable();

        static::$isSearchableTraitBooted = true;
    }


    /**
     * Get the index configurator.
     *
     * @return \Larelastic\Elastic\Models\IndexConfigurator;
     * @throws \Exception
     */
    public function getIndexConfigurator()
    {
        static $indexConfigurator;

        if (!$indexConfigurator) {
            if (!isset($this->indexConfigurator) || empty($this->indexConfigurator)) {
                throw new Exception(sprintf(
                    'An index configurator for the %s model is not specified.',
                    __CLASS__
                ));
            }

            $indexConfiguratorClass = $this->indexConfigurator;
            $indexConfigurator = new $indexConfiguratorClass;
        }

        return $indexConfigurator;
    }

    /**
     * Execute the search.
     *
     * @param string $query
     * @param callable|null $callback
     *
     */
    public static function search($query=null,$callback = null)
    {
        $softDelete = static::usesSoftDelete() && config('scout.soft_delete', false);

        //index name should be set on model
        $model = new static();

        return new QueryBuilder(new static, $query, $callback, $softDelete);
    }
    
    /**
     * Make all instances of the model searchable.
     *
     * @return void
     */
    public static function makeAllSearchable()
    {
        $self = new static;
        $softDelete = static::usesSoftDelete() && config('scout.soft_delete', false);
        $self->searchableQuery()
            ->when($softDelete, function ($query) {
                $query->withTrashed();
            })
            ->orderBy($self->getKeyName())
            ->searchable();
    }
    
    /**
     * Override this method to provide a query for efficient indexing.
     * e.g. directives for eager loading related model data
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function searchableQuery()
    {
        return $this->newQuery();
    }

    /**
     * Get the key name used to index the model.
     *
     * @return mixed
     */
    public function getScoutKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Get the index name for the model.
     *
     * @return string
     */
    public function searchableAs()
    {
        return config('scout.prefix').$this->getTable();
    }
}