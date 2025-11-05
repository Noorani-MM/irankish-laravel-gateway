<?php

namespace IranKish;

use Illuminate\Support\ServiceProvider;

class IranKishServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/irankish.php',
            'irankish'
        );

        $this->app->singleton(IranKish::class, function ($app) {
            return new IranKish($app['config']->get('irankish', []));
        });

        // Optional alias via container key
        $this->app->alias(IranKish::class, 'irankish');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/irankish.php' => $this->getConfigPath(),
            ], 'config');
        }
    }

    protected function getConfigPath()
    {
        return function_exists('config_path')
            ? config_path('irankish.php')
            : $this->app->basePath() . '/config/irankish.php';
    }
}
