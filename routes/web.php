<?php

use Illuminate\Support\Facades\Route;

Route::get('/', 'BullshitController@index');

Route::post('register', 'AuthController@register');
Route::post('login', 'AuthController@login');
Route::post('reset_password', 'AuthController@requestPasswordReset');
Route::post('reset_password/set', 'AuthController@resetPassword');

Route::get('roles/setup', 'RoleController@createRoles');

Route::get('key', 'BullshitController@randomKey');

// Attach user if available, but open to guests.
Route::group(['middleware' => 'auth'], function() {
  Route::get('submissions', 'SubmissionController@index');
  Route::get('submissions/{id}', 'SubmissionController@show');
  Route::get('submissions/{id}/comments', 'CommentController@showSubmission');

  Route::get('users/{id}', 'UserController@show');
  Route::get('users/{id}/submissions', 'UserController@submissions');

  Route::get('/streaks/end', 'StreakController@endExpired');

  Route::get('leaderboard', 'SubmissionController@monthlyLeaderboard');
});

// Only authed users.
Route::group(['middleware' => 'protect'], function() {
  Route::get('verify_token', 'BullshitController@ok');

  Route::get('plans', 'PlanController@index');
  Route::put('plans/{id}', 'PlanController@update');
  Route::post('plans', 'PlanController@store');
  Route::delete('plans/{id}', 'PlanController@destroy');

  Route::get('tags', 'TagController@index');
  Route::put('tags/{id}', 'TagController@update');
  Route::post('tags', 'TagController@store');
  Route::delete('tags/{id}', 'TagController@destroy');

  Route::put('submissions/{id}', 'SubmissionController@update');
  Route::post('submissions', 'SubmissionController@store');
  Route::delete('submissions/{id}', 'SubmissionController@destroy');
  Route::post('submissions/{id}/comments', 'CommentController@storeSubmission');
  Route::put('submissions/{id}/like', 'SubmissionController@likeSubmission');

  Route::get('followed_submissions', 'SubmissionController@followedIndex');

  Route::get('streaks/current', 'StreakController@show');

  Route::put('comments/{id}', 'CommentController@update');
  Route::post('comments/{id}', 'CommentController@reply');

  Route::get('notifications', 'NotificationController@index');
  Route::post('notifications/seen', 'NotificationController@seen');

  Route::get('avatar', 'UserController@getAvatar');
  Route::post('avatar', 'UserController@updateAvatar');

  Route::post('social_links', 'SocialLinkController@store');
  Route::delete('social_links/{id}', 'SocialLinkController@destroy');

  Route::put('users/{id}/follow', 'UserController@followUser');
});
