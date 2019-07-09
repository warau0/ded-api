<?php

namespace App\Http\Controllers;


use App\User;
use App\Http\Controllers\Controller;
use App\Rules\ValidRecaptcha;
use App\Facades\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use \Firebase\JWT\JWT;

class AuthController extends Controller {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.USER'), $code, $userID, $msg);
  }

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
      'recaptcha' => ['required', new ValidRecaptcha]
    ]);

    $user = User::create([
      'username' => $request->input('username'),
      'email' => $request->input('email'),
      'password' => Hash::make($request->input('password')),
    ]);

    if($user->save()) {
      $this->log(1, $user->id, 'Register - success');
      return response()->json(['token' => $this->jwt($user)], Response::HTTP_OK);
    } else {
      $this->log(2, null, 'Register - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function login(Request $request) {
    $user = User::where('username', $request->input('username'))->first();

    if(empty($user)) {
      $this->log(3, null, 'Login - invalid username');
      return response()->json(['error' => 'Invalid username.'], 401);
    }

    if (Hash::check($request->input('password'), $user->password)) {
      $this->log(4, $user->id, 'Login - success');
      return response()->json(['token' => $this->jwt($user)]);
     } else {
      $this->log(5, null, 'Login - failed');
      return response()->json(['error' => 'Invalid password.'], 401);
    }
  }
}
