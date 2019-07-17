<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Notification extends Model {
  use softDeletes;

  protected $fillable = [
    'user_id',
    'seen',
    'text',
    'notification_parent_id',
    'notification_parent_type',
  ];

  protected $hidden = ['deleted_at'];

  protected $dates = ['deleted_at'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function notifiable() {
    return $this->morphTo();
  }
}
