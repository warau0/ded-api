<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Comment extends Model {
  use softDeletes;

  protected $fillable = [
    'text',
    'user_id',
    'anonymous',
    'comment_parent_id',
    'comment_parent_type',
  ];

  protected $hidden = ['deleted_at'];

  protected $dates = ['deleted_at'];

  public function getUserIdAttribute($value) {
      return $this->anonymous ? null : $value;
  }

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function commentable() {
    return $this->morphTo();
  }

  public function comments() {
    return $this->morphMany(Comment::class, 'commentable', 'comment_parent_type', 'comment_parent_id')
      ->with(['comments.user', 'user']);
  }
}
