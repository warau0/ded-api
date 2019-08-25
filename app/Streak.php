<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Extensions\Database\Eloquent\SoftDeletes;
use App\Facades\Util;

class Streak extends Model {
  use softDeletes;

  protected $fillable = [
    'user_id',
    'frequency',
    'count',
    'end',
  ];

  protected $hidden = [
    'deleted_at'
  ];

  protected $dates = ['deleted_at'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  // Check if the streak hasn't been updated and is now dead.
  private function isExpired() {
    $updatedDaysAgo = Carbon::now()->setTime(0, 0, 0)
      ->diff(Carbon::parse($this->updated_at)->setTime(0, 0, 0))->days;

    return $updatedDaysAgo > $this->frequency;
  }

  // Check if it's time to bump up streak.
  private function alreadyUpdated() {
    if ($this->count == 0) return false; // Always let empty streaks be updated.

    $updatedDaysAgo = Carbon::now()->setTime(0, 0, 0)
        ->diff(Carbon::parse($this->updated_at)->setTime(0, 0, 0))->days;

    return $updatedDaysAgo < $this->frequency; // Only bump streak when posting on the last day
    // If post frequency is 4 days, you MUST post 4 days apart for streak to update and not expire.
    // If posted on day 3, it will say streak has already been updated and ignore updating streak.
    // If posting on day 5 the streak has died.
  }

  // Try to increase streak.
  public function attemptBump() {
    if ($this->alreadyUpdated()) {
      Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Update streak ' . $this->id . ' - already updated');

      return false;
    }

    if ($this->isExpired()) {
      // Should never happen, streak should already have been killed by day rollover job.
      Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Update streak ' . $this->id . ' - manually ending');
      $this->end = Carbon::now();
      $this->save();

      $streak = new Streak();
      $streak->count = 1;
      $streak->user_id = $this->user_id;
      $streak->frequency = $this->frequency;
      if ($streak->save()) {
        Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Create streak ' . $streak->id . ' - success');
      } else {
        Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Create streak - failed');
      }

      return true;
    }

    $this->count++;
    Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Update streak ' . $this->id . ' - bump to ' . $this->count);

    return $this->save();
  }
}
