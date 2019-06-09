<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Plan extends Model {
  use SoftDeletes;

  protected $fillable = [
    'day',
    'start',
    'duration',
    'color',
    'text',
    'user_id',
  ];

  protected $dates = [
    'deleted_at'
  ];

  protected $hidden = [
    'deleted_at'
  ];
}
