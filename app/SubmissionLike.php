<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Extensions\Database\Eloquent\SoftDeletes;

class SubmissionLike extends Model {
  use softDeletes;

  protected $fillable = [
    'user_id',
    'submission_id',
  ];

  protected $hidden = ['deleted_at'];

  protected $dates = ['deleted_at'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function submission() {
    return $this->belongsTo(Submission::class);
  }
}
