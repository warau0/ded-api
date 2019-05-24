<?php

app('router')->post('register', 'AuthController@register');
app('router')->post('login', 'AuthController@login');

app('router')->get('key', function() { return str_random(32); });

app('router')->group(['middleware' => 'auth'], function() {
  // TODO
  app('router')->get('notifications', function() { return ["notifications" => []]; });

  // TODO
  app('router')->get('notifications', function() {
    return ["plans" => [
      0 => [
        0 => [ "duration" => 1, "brand" => 'tomato', "text" => 'Plan 1' ],
        1 => [ "duration" => 1, "brand" => 'flamingo', "text" => 'Plan 2' ],
        2 => [ "duration" => 1, "brand" => 'tangerine', "text" => 'Plan 3' ],
        3 => [ "duration" => 1, "brand" => 'banana', "text" => 'Plan 4' ],
        4 => [ "duration" => 1, "brand" => 'sage', "text" => 'Plan 5' ],
        5 => [ "duration" => 1, "brand" => 'basil', "text" => 'Plan 6' ],
        6 => [ "duration" => 1, "brand" => 'peacock', "text" => 'Plan 7' ],
        7 => [ "duration" => 1, "brand" => 'blueberry', "text" => 'Plan 8' ],
        8 => [ "duration" => 1, "brand" => 'lavender', "text" => 'Plan 9' ],
        9 => [ "duration" => 1, "brand" => 'grape', "text" => 'Plan 10' ],
        10 => [ "duration" => 1, "brand" => 'graphite', "text" => 'Plan 11' ],
        11 => [ "duration" => 1, "brand" => 'sky', "text" => 'Plan 12' ],
      ],
      4 => [
        17 => [ "duration" => 7, "brand" => 'sky', "text" => 'Call dad' ],
      ],
      5 => [
        5 => [ "duration" => 2, "brand" => 'sky', "text" => 'Hello' ],
      ],
      6 => [
        0 => [ "duration" => 24, "brand" => 'sky', "text" => 'Wooo saturday' ],
      ],
    ]];
  });
});
