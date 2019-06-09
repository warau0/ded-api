<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class User extends Model {
  use SoftDeletes;

  protected $fillable = [
    'username',
    'email',
    'password',
  ];

  protected $hidden = [
    'password',
    'deleted_at',
    'email',
  ];

  protected $dates = [
    'deleted_at'
  ];
}
