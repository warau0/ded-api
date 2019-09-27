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

  public function submissions() {
    return $this->hasMany(Submission::class);
  }

  public function notifications() {
    return $this->hasMany(Notification::class);
  }

  public function avatar() {
    return $this->morphOne(Image::class, 'imageable', 'image_parent_type', 'image_parent_id');
  }

  public function socialLinks() {
    return $this->hasMany(SocialLink::class);
  }

  public function roles() {
    return $this->belongsToMany(Role::class, 'user_roles');
  }

  public function tags() {
    return $this->hasMany(Tag::class);
  }
}
