<?php

app('router')->post('register', 'AuthController@register');
app('router')->post('login', 'AuthController@login');

app('router')->get('key', function() { return str_random(32); });

app('router')->group(['middleware' => 'auth'], function() {
  app('router')->get('verify_token', function() { return 'OK'; });

  // TODO
  app('router')->get('notifications', function() { return ["notifications" => []]; });

  app('router')->get('plans', 'PlanController@index');
  app('router')->put('plans/{id}', 'PlanController@update');
  app('router')->post('plans', 'PlanController@store');
  app('router')->delete('plans/{id}', 'PlanController@destroy');

  app('router')->get('tags', 'TagController@index');
  app('router')->put('tags/{id}', 'TagController@update');
  app('router')->post('tags', 'TagController@store');
  app('router')->delete('tags/{id}', 'TagController@destroy');
});
