<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use App\LibExtension\LogEx;

class BroadcastServiceProvider extends ServiceProvider
{
    protected $className = "BroadcastServiceProvider";
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        LogEx::bootName($this->className, 'boot');

        Broadcast::routes(['middleware' => ['auth']]);

        require base_path('routes/channels.php');
    }
}
