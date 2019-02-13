<?php
/**
 * Created by PhpStorm.
 * User: cennis
 * Date: 2/9/19
 * Time: 2:19 PM
 */

namespace ccennis\Larelastic\Providers;


class LarelasticServiceProvider
{
    public function boot()
    {
        //
    }

    public function provides()
    {
        return ['han.prince'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ccennis.larelastic', function ($app) {
            $larelastic = $this->app->make('ccennis\Services\LarelasticService');

            return $larelastic;
        });
    }
}