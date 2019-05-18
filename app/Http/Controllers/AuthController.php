<?php

namespace App\Http\Controllers;


use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use \Firebase\JWT\JWT;

class AuthController extends Controller {
  // Generate JWT token for a user.
  protected function jwt(User $user) {
    $payload = [
        'iss' => "ded-api",
        'sub' => $user->id,
        'username' => $user->username,
        'iat' => time(),
        'exp' => time() + 60*60*72, // 3 days
    ];

    return JWT::encode($payload, env('JWT_KEY'));
  }

  public function register(Request $request) {
    $this->validate($request, [
      'username' => 'required|string|max:50|unique:users,username',
      'email' => 'sometimes|string|email|max:255|nullable',
      'password' => 'required|confirmed|min:6',
    ]);

    $user = User::create([
      'username' => $request->input('username'),
      'email' => $request->input('email'),
      'password' => app('hash')->make($request->input('password')),
    ]);

    if($user->save()) {
      return response()->json(['token' => $this->jwt($user)], Response::HTTP_OK);
    } else {
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function login(Request $request) {
    $user = User::where('username', $request->input('username'))->first();

    if(empty($user)) {
      return response()->json(['error' => 'Invalid username.'], 401);
    }

    if (app('hash')->check($request->input('password'), $user->password)) {
         return response()->json(['token' => $this->jwt($user)]);
     } else {
        return response()->json(['error' => 'Invalid password.'], 401);
     }
  }
}
