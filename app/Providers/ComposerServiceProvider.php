<?php
/**
 * Created by PhpStorm.
 * User: anhdv
 * Date: 6/30/2018
 * Time: 11:09 AM
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;
use App\LibExtension\LogEx;

/**
 * Class ComposerServiceProvider
 * @package App\Providers
 */
class ComposerServiceProvider extends ServiceProvider
{
    protected $className = "ComposerServiceProvider";
    /**
     * Register bindings in the container.
     *
     * @param \Illuminate\Contracts\View\Factory $view
     * @return void
     */
    public function boot(ViewFactory $view)
    {
        LogEx::bootName($this->className, 'boot');

        $view->composer('*', 'App\Http\ViewComposers\HydroComposer');
    }

    public function register()
    {
        LogEx::registerName($this->className, 'register');

        //
    }
}
