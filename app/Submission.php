<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Submission extends Model {
  use softDeletes;

  protected $fillable = [
    'user_id',
    'description',
    'hours',
    'nsfw',
    'private',
  ];

  protected $hidden = [
    'deleted_at'
  ];

  protected $dates = ['deleted_at'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function images() {
    return $this->morphMany(Image::class, 'imageable', 'image_parent_type', 'image_parent_id');
  }

  public function tags() {
    return $this->belongsToMany(Tag::class, 'submission_tags');
  }
}
