<?php

namespace ccennis\larelastic\Providers;

use Illuminate\Support\ServiceProvider;

class LarelasticServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/larelastic.php' => config_path('larelastic.php'),
        ]);
    }

    public function provides()
    {
        return ['ccennis.larelastic'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ccennis.larelastic', function ($app) {
            $larelastic = $this->app->make('ccennis\larelastic\Services\ElasticService');

            return $larelastic;
        });
    }
}