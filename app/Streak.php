<?php

namespace App;

use App\Facades\Util;
use App\Traits\Notifies;
use App\Extensions\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Streak extends Model {
  use softDeletes, Notifies;

  protected $fillable = [
    'user_id',
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

  // Check if the streak hasn't been updated in a week and is now dead.
  private function isExpired() {
    $updatedDaysAgo = Carbon::now()->setTime(0, 0, 0)
      ->diff(Carbon::parse($this->updated_at)->setTime(0, 0, 0))->days;

    return $updatedDaysAgo > 7;
  }

  // Check if it's time to bump up streak.
  private function alreadyUpdated() {
    if ($this->count == 0) return false; // Always let empty streaks be updated.

    $now = Carbon::now()->setTime(0, 0, 0);
    $updated = Carbon::parse($this->updated_at)->setTime(0, 0, 0);
    $updatedDaysAgo = $now->diff($updated)->days;

    return $updatedDaysAgo < 1; // Bump streak if it's been a day since last time.
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
      $this->createStreakEndNotification($this->user_id, $this->count);

      $streak = new Streak();
      $streak->count = 1;
      $streak->user_id = $this->user_id;
      if ($streak->save()) {
        Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Create streak ' . $streak->id . ' - success');
        return true;
      } else {
        Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Create streak - failed');
        return false;
      }
    }

    $this->count++;
    Util::logLine(config('constants.LOG.STREAK'), $this->user_id, 'Update streak ' . $this->id . ' - bump to ' . $this->count);

    return $this->save();
  }
}
