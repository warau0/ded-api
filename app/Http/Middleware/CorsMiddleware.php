<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class CorsMiddleware {
  public function handle($request, Closure $next) {
    $headers = [
      'Access-Control-Allow-Origin' => '*',
      'Access-Control-Allow-Methods' => 'GET, PUT, POST, PATCH, DELETE, OPTIONS',
      'Access-Control-Allow-Headers' => $request->header('Access-Control-Request-Headers')
    ];

    if ($request->isMethod('OPTIONS')) {
      return response(null, Response::HTTP_NO_CONTENT)->withHeaders($headers);
    }

    $response = $next($request);
    foreach($headers as $key => $value) {
      $response->header($key, $value);
    }

    return $response;
  }
}