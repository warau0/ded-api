<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model {
  use SoftDeletes;

  protected $fillable = [
    'color',
    'text',
    'user_id',
  ];

  protected $dates = [
    'deleted_at'
  ];
}
