<?php

namespace Weebly\Mutate;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class LaravelMutatorServiceProvider extends ServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('mutators.php')
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function register()
    {
        $this->app->singleton('mutator', function (Application $app) {
            $mutator  = new MutatorProvider();
            $mutator->registerMutators($app['config']->get('mutators.enabled'));

            return $mutator;
        });
    }
}
