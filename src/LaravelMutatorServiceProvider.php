<?php

namespace Weebly\Mutate;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class LaravelMutatorServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('mutators.php'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function register()
    {
        $this->app->singleton('mutator', function (Application $app) {
            $mutator = new MutatorProvider();
            $mutator->registerMutators($app['config']->get('mutators.enabled'));

            return $mutator;
        });
    }
}
