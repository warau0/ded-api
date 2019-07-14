<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use \Firebase\JWT\JWT;
use \Firebase\JWT\ExpiredException;

class Protect {
  public function handle($request, Closure $next, $guard = null) {
    $token = $request->header('Authorization');

    if (!$token) {
        return response()->json(['error' => 'Unauthorized.'], 401);
    }

    try {
      $token = explode(' ', $token)[1];
      $credentials = JWT::decode($token, env('JWT_KEY'), ['HS256']);
    } catch(ExpiredException $e) {
        return response()->json(['error' => 'Expired auth.'], 401);
    } catch(\Exception $e) {
        return response()->json(['error' => 'Invalid auth.'], 401);
    }

    $user = User::find($credentials->sub);
    if (!$user) {
      return response()->json(['error' => 'Invalid auth.'], 401);
    }

    $request->user = $user;
    return $next($request);
  }
}
