<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\LibExtension\LogEx;
class EventServiceProvider extends ServiceProvider
{
    protected $className = "EventServiceProvider";
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\Event' => [
            'App\Listeners\EventListener',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        LogEx::bootName($this->className, 'boot');

        parent::boot();

        //
    }
}
