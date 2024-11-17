<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\LibExtension\LogEx;

class AuthServiceProvider extends ServiceProvider
{
    protected $className = "AuthServiceProvider";
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        LogEx::bootName($this->className, 'boot');

        $this->registerPolicies();

        //
    }

}
