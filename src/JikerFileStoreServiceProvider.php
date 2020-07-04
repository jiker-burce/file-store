<?php

namespace Jiker\FileStore;

use Carbon\Laravel\ServiceProvider;
use Jiker\FileStore\Managers\FileStoreApiManager;

/**
 * Created by PhpStorm.
 * User: gujinhe
 * Date: 2020/7/3
 * Time: 11:48 AM
 */


class JikerFileStoreServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/routes.php');
    
        $this->publishes([
            __DIR__.'/databases/migrations/' => database_path('migrations')
        ], 'migrations');
    
        // $this->publishes([
        //     __DIR__.'/databases/seeds/' => database_path('seeds')
        // ], '');
    }
    
    public function register()
    {
        $this->app->singleton('jiker_file_store', function () {
            return new FileStoreApiManager();
        });
    }
}
