<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/9/19
 * Time: 2:19 PM
 */

namespace ccennis\Larelastic\Providers;

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
        //
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
            $larelastic = $this->app->make('ccennis\Larelastic\Services\ElasticService');

            return $larelastic;
        });
    }