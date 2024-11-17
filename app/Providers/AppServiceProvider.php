<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\LibExtension\LogEx;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    protected $className = "AppServiceProvider";
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        LogEx::bootName($this->className, 'boot');

        // Query log
        if (env('APP_DEBUG')) {
            DB::listen(function ($query) {
                LogEx::logSQL($query->sql, $query->bindings, $query->time);
            });
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        LogEx::registerName($this->className, 'register');

        //
    }
}
