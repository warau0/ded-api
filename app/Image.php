<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Image extends Model {
  use softDeletes;

  protected $fillable = [
    'file',
    'url',
    'hash',
    'size',
    'height',
    'width',
    'mime',
    'image_parent_id',
    'image_parent_type',
  ];

  protected $hidden = [
    'deleted_at',
    'path',
    'hash',
  ];

  protected $dates = ['deleted_at'];

  public function imageable() {
    return $this->morphTo();
  }

  public function thumbnail() {
    return $this->morphOne(Image::class, 'imageable', 'image_parent_type', 'image_parent_id');
  }
}
