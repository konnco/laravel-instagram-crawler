<?php

namespace Konnco\InstagramCrawler;

use Illuminate\Support\ServiceProvider;

class InstagramCrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
    }

    public function publishConfig()
    {
        $this->publishes([__DIR__.'/config/instagram.php' => config_path('instagram.php')], 'instagram');
        $this->mergeConfigFrom(__DIR__.'/config/instagram.php', 'instagram');
    }
}
