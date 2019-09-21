<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Role extends Model {
  use softDeletes;

  protected $fillable = [
    'title',
    'slug',
    'visible',
  ];

  protected $hidden = [
    'deleted_at',
  ];

  protected $dates = ['deleted_at'];

  public function users() {
    return $this->belongsToMany(User::class, 'user_roles');
  }
}
