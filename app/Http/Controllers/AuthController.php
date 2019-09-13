<?php

namespace App\Http\Controllers;


use App\User;
use App\PasswordReset;
use App\Http\Controllers\Controller;
use App\Rules\ValidRecaptcha;
use App\Facades\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use \Firebase\JWT\JWT;

class AuthController extends Controller {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.USER'), $userID, $msg);
  }

  // Generate JWT token for a user.
  protected function jwt(User $user) {
    $payload = [
        'iss' => "ded-api",
        'sub' => $user->id,
        'username' => $user->username,
        'iat' => time(),
        'exp' => time() + 60*60*24 * 30, // 30 days
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

    // TODO: Reject if username has special HTML characters.
    $user = User::create([
      'username' => trim(preg_replace('/\s+/', ' ', $request->input('username'))), // Combine and trim spaces
      'email' => $request->input('email'),
      'password' => Hash::make($request->input('password')),
    ]);

    if($user->save()) {
      $this->log($user->id, 'Register - success');
      return response()->json(['token' => $this->jwt($user)], Response::HTTP_OK);
    } else {
      $this->log(null, 'Register - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function login(Request $request) {
    $user = User::where('username', $request->input('username'))->first();

    if (empty($user)) {
      $this->log(null, 'Login - invalid username');
      return response()->json(['error' => 'Invalid username.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (Hash::check($request->input('password'), $user->password)) {
      $this->log($user->id, 'Login - success');
      return response()->json(['token' => $this->jwt($user)]);
     } else {
      $this->log(null, 'Login - failed');
      return response()->json(['error' => 'Invalid password.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
  }

  public function requestPasswordReset(Request $request) {
    $this->validate($request, [
      'username' => 'required|string|max:50',
    ]);

    $user = User::query()
      ->where('username', $request->input('username'))
      ->first();

    if (!isset($user)) {
      $this->log(null, 'Reset password - invalid username');
      return response()->json(['error' => 'No user found with that username.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (!isset($user->email)) {
      $this->log(null, 'Reset password - no email');
      return response()->json(['error' => 'This user has no email registered.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $deleteResult = PasswordReset::query()
      ->where('user_id', $user->id)
      ->delete();

    if ($deleteResult) {
      $this->log($user->id, 'Reset password - Old tokens deleted: ' . $deleteResult);
    }

    $passwordReset = PasswordReset::create([
      'user_id' => $user->id,
      'token' => str_random(12),
    ]);

    if($passwordReset->save()) {
      $mailError = Util::sendPasswordReset($user, $passwordReset);
      if (!$mailError) {
        $this->log($user->id, 'Reset password - email sent');
        return response()->json(['status' => 'OK'], Response::HTTP_OK);
      } else {
        $this->log($user->id, 'Reset password - email failed: ' . $mailError);
        return response()->json(['error' => $mailError], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    } else {
      $this->log($user->id, 'Reset password - create row failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function resetPassword(Request $request) {
    $this->validate($request, [
      'user_id' => 'required|integer',
      'token' => 'required|string',
      'password' => 'required|confirmed|min:6',
    ]);

    $reset = PasswordReset::query()
      ->where([
        ['user_id', '=', $request->input('user_id')],
        ['token', '=', $request->input('token')],
      ])->first();

    if (!isset($reset)) {
      $this->log($request->input('user_id'), 'Reset password - no matching request found');
      return response()->json(['error' => 'Invalid password reset token. Request a new password reset if the issue persists.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $update = User::query()
      ->where('id', $request->input('user_id'))
      ->update(['password' => Hash::make($request->input('password'))]);
    $user = User::find($request->input('user_id'));

    if($update) {
      $reset->delete();
      $this->log($user->id, 'Reset password - success');
      return response()->json(['token' => $this->jwt($user)], Response::HTTP_OK);
    } else {
      $this->log($request->input('user_id'), 'Reset password - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
