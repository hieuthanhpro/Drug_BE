<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use App\LibExtension\LogEx;

class RouteServiceProvider extends ServiceProvider
{
    protected $className = "RouteServiceProvider";
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        LogEx::bootName($this->className, 'boot');

        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        LogEx::mapName($this->className, 'map');

        $this->mapApiRoutes();

        $this->mapApiV3Routes();

        $this->mapWebRoutes();
        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        LogEx::mapWebRoutesName($this->className, 'mapWebRoutes');

        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        LogEx::mapApiRoutesName($this->className, 'mapApiRoutes');

        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }

    protected function mapApiV3Routes()
    {
        LogEx::mapApiRoutesName($this->className, 'mapApiRoutes');
        Route::prefix('api/v3')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/apiv3.php'));
    }
}
