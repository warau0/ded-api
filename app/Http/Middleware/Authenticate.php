<?php

namespace App\Http\Middleware;

use Closure;
use App\User;
use \Firebase\JWT\JWT;
use \Firebase\JWT\ExpiredException;

class Authenticate {
  public function handle($request, Closure $next, $guard = null) {
    $token = $request->header('Authorization');

    if ($token) {
      try {
        $token = explode(' ', $token)[1];
        $credentials = JWT::decode($token, env('JWT_KEY'), ['HS256']);
        $user = User::find($credentials->sub);

        $request->user = $user;
      } catch(\Exception $e) {}
    }

    return $next($request);
  }
}
