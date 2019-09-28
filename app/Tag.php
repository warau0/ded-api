<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;

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

  protected $hidden = [
    'deleted_at'
  ];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function submissions() {
    return $this->belongsToMany(Submission::class, 'submission_tags');
  }
}
