<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Extensions\Database\Eloquent\SoftDeletes;

class UserFollow extends Model {
  use softDeletes;

  protected $primaryKey = null;
  public $incrementing = false;

  protected $fillable = [
    'user_id',
    'follow_id',
  ];

  protected $hidden = ['deleted_at'];

  protected $dates = ['deleted_at'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function follow() {
    return $this->belongsTo(User::class);
  }
}
