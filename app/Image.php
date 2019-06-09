<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

class Image extends Model {
  use softDeletes;

  protected $fillable = [
    'path',
    'name',
    'hash',
    'size',
    'height',
    'width',
    'extension',
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
}
