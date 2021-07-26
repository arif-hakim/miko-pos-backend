<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Lib\Response;
use Firebase\JWT\JWT;
use App\Models\User;

class AccessToken
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
            $decoded = JWT::decode($token, $secret, array('HS256'));
            
            $user = User::whereEmail($decoded->email)->first();
            if(!$user) return Response::unauthorized('Unauthorized.', 'User doesn\'t exists.');
            
            $request->authenticatedUser = $user;
            $request->authenticatedToken = $token;

            return $next($request);
        } catch (\Exception $e){
            return Response::unauthorized('Unauthorized.', $e->getMessage());
        }
    }
}
