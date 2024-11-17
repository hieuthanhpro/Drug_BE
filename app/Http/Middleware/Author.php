<?php

namespace App\Http\Middleware;

use App\LibExtension\CommonConstant;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\LibExtension\LogEx;
use Tymon\JWTAuth\Facades\JWTAuth;
use Closure;

class Author
{
    protected $className = "Author";
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        LogEx::authName($this->className, 'handle');

        try {
            $request['userInfo'] = JWTAuth::toUser($request->header('token'));
            $user = $request['userInfo'];
            $request->merge(['user' => $user]);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
            Auth::setUser($user);
        } catch (JWTException $e) {
            return \App\Helper::errorResponse(CommonConstant::UNAUTHORIZED, CommonConstant::MSG_ERROR_ACCESSDENIED);
        }
        return $next($request);
    }
}
