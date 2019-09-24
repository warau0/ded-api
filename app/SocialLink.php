<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class SocialLink extends Model {
  use softDeletes;

  protected $fillable = [
    'user_id',
    'link',
  ];

  protected $hidden = [
    'deleted_at'
  ];

  protected $dates = ['deleted_at'];

  public function user() {
    return $this->belongsTo(User::class);
  }
}
