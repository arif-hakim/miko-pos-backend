<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Unit;
use Firebase\JWT\JWT;
use App\Lib\Response;

class MakeOrderToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if (!$token) return Response::unauthorized('Unauthorized.', 'Token doesn\'t exists');

        try {
            $secret = config('app.jwt_secret');
            $id = JWT::decode($token, $secret, array('HS256'));
            
            $unit = Unit::find($id);
            if(!$unit) return Response::unauthorized('Unauthorized.', 'User doesn\'t exists.');
            
            $request->authenticatedUnit = $unit;
            $request->authenticatedToken = $token;

            return $next($request);
        } catch (\Exception $e){
            return Response::unauthorized('Unauthorized.', $e->getMessage());
        }
    }
}
