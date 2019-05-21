<?php

app('router')->post('register', 'AuthController@register');
app('router')->post('login', 'AuthController@login');

app('router')->get('key', function() { return str_random(32); });

app('router')->group(['middleware' => 'auth'], function() {
  // TODO
  app('router')->get('notifications', function() { return ["notifications" => []]; });
});
