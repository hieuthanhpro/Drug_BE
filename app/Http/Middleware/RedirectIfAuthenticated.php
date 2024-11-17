<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\LibExtension\LogEx;

class RedirectIfAuthenticated
{
    protected $className = "AbstractBaseRepository";

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        LogEx::authName($this->className, 'handle');

        if (Auth::guard($guard)->check()) {
            return redirect('/admin/thuoc');
        }

        return $next($request);
    }
}
