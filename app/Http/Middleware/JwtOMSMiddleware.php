<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
// use Tymon\JWTAuth;
use Exception;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Auth;

class JwtOMSMiddleware extends BaseMiddleware
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
     
        try {
            $user = Auth::guard('oms')->authenticate();

           
        } catch (Exception $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException){
                return response()->json(['status'=> 401,'message' => 'Token is Invalid']);
            }else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException){
                return response()->json(['status'=> 401,'status' => 'Token is Expired']);
            }else{
                return response()->json(['code'=> 401,'status' => 'Authorization Token not found']);
            }
        }
        return $next($request);
    }
}
