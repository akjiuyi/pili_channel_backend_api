<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Log;
use Laravel\Lumen\Application;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('Log', function (Application $app) {
            return new Log();
        });
    }
}
