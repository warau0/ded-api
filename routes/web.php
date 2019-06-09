<?php

use Illuminate\Support\Facades\Route;

Route::post('register', 'AuthController@register');
Route::post('login', 'AuthController@login');

Route::get('key', function() { return str_random(32); });

Route::get('submissions', 'SubmissionController@index');

Route::group(['middleware' => 'auth'], function() {
  Route::get('verify_token', function() { return 'OK'; });

  // TODO
  Route::get('notifications', function() { return ["notifications" => []]; });

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
});
