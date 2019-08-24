<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use Illuminate\Http\Response;
use \Firebase\JWT\JWT;
use \Firebase\JWT\ExpiredException;

class Protect {
  public function handle($request, Closure $next, $guard = null) {
    $token = $request->header('Authorization');

    if (!$token) {
        return response()->json(['error' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
    }

    try {
      $token = explode(' ', $token)[1];
      $credentials = JWT::decode($token, env('JWT_KEY'), ['HS256']);
    } catch(ExpiredException $e) {
        return response()->json(['error' => 'Expired auth.'], Response::HTTP_UNAUTHORIZED);
    } catch(\Exception $e) {
        return response()->json(['error' => 'Invalid auth.'], Response::HTTP_UNAUTHORIZED);
    }

    $user = User::find($credentials->sub);
    if (!$user) {
      return response()->json(['error' => 'Invalid auth.'], Response::HTTP_UNAUTHORIZED);
    }

    $request->user = $user;
    return $next($request);
  }
}
