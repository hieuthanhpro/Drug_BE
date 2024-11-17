<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\LibExtension\LogEx;
use Tymon\JWTAuth\Facades\JWTAuth;


class VerifyJWTToken
{
    protected $className = "AbstractBaseRepository";

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        LogEx::debug($this->className, 'handle');

        try {
            JWTAuth::toUser($request->header('token'));
        }catch (JWTException $e) {
            LogEx::try_catch($this->className, $e);
            if($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
                return response()->json(['token_expired'], $e->getStatusCode());
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
                return response()->json(['token_invalid'], $e->getStatusCode());
            }else{
                return response()->json(['error'=>'Token is required 1']);
            }
        }
        return $next($request);
    }
}
