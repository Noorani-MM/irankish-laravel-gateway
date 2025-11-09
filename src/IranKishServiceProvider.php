<?php

namespace IranKish;

use Illuminate\Support\ServiceProvider;

class IranKishServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/irankish.php', 'irankish');

        $this->app->singleton('irankish.manager', function ($app) {
            return new IranKishManager($app['config']->get('irankish'));
        });

        $this->app->singleton('irankish.gateway', function ($app) {
            /** @var IranKishManager $manager */
            $manager = $app->make('irankish.manager');
            return $manager->gateway();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/irankish.php' => config_path('irankish.php'),
        ], 'config');
    }
}
