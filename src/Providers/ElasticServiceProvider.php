<?php

namespace Larelastic\Elastic\Providers;

use Larelastic\Elastic\Console\ElasticIndexDropCommand;
use Larelastic\Elastic\Console\ElasticUpdateMappingCommand;
use Larelastic\Elastic\Console\ElasticIndexUpdateCommand;
use Larelastic\Elastic\Console\ElasticMigrateCommand;
use Larelastic\Elastic\Console\IndexConfiguratorMakeCommand;
use Larelastic\Elastic\Console\SearchableModelMakeCommand;
use Larelastic\Elastic\ElasticEngine;
use Illuminate\Support\ServiceProvider;
use Larelastic\Elastic\Console\ElasticIndexCreateCommand;
use InvalidArgumentException;
use Laravel\Scout\EngineManager;
use Elasticsearch\ClientBuilder;
use function class_exists;
use function sprintf;
use function ucfirst;

class ElasticServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/elastic.php' => config_path('elastic.php'),
        ]);

        $this->commands([
            // make commands
            IndexConfiguratorMakeCommand::class,
            SearchableModelMakeCommand::class,

            // elastic commands
            ElasticIndexCreateCommand::class,
            ElasticIndexUpdateCommand::class,
            ElasticIndexDropCommand::class,
            ElasticUpdateMappingCommand::class,
            ElasticMigrateCommand::class,

        ]);

        $this->app->make(EngineManager::class)->extend('elastic', function () {
            $indexerType = config('elastic.indexer', 'single');
            $updateMapping = config('elastic.update_mapping', true);
            $indexerClass = '\\Larelastic\\Elastic\\Indexers\\' . ucfirst($indexerType) . 'Indexer';

            if (!class_exists($indexerClass)) {
                throw new InvalidArgumentException(sprintf(
                    'The %s indexer doesn\'t exist.',
                    $indexerType
                ));
            }

            return new ElasticEngine(new $indexerClass(), $updateMapping);

        });
    }

    public function provides()
    {
        return ['larelastic.elastic'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //get the facade that powers an elastic connection instance
        $this->app->singleton('larelastic.elastic', function () {

            //if we have a pw, it needs it. otherwise its a dev env
            $isPwProtected = config('elastic.client.password');

            $hosts = !empty($isPwProtected) ? [config('elastic.client.auth_string')] : config('elastic.client.hosts');

            $es = new ClientBuilder();
            return $es->create()
                ->setConnectionParams([
                    'client' => [
                        'timeout'         => config('elastic.client.timeout_in_seconds'),
                        'connect_timeout' => config('elastic.client.connect_timeout_in_seconds'),
                    ]])
                ->setHosts($hosts)
                ->build();
        });
    }
}
