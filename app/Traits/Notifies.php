<?php

namespace App\Traits;

use App\Notification;
use App\Submission;
use App\Facades\Util;

trait Notifies {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.NOTIFICATION'), $userID, $msg);
  }

  public function createSubmissionNotification($text, $userID, $submissionID) {
    $notification = new Notification([
      'user_id' => $userID,
      'text' => $text,
      'notification_parent_id' => $submissionID,
      'notification_parent_type' => Submission::class,
    ]);

    if ($notification->save()) {
      $this->log($userID, 'Create submission notification ' . $notification->id . ' - success');
      return true;
    } else {
      $this->log($userID, 'Create submission notification - failed');
      return false;
    }
  }

  public function createStreakEndNotification($userID, $streak) {
    $notification = new Notification([
      'user_id' => $userID,
      'text' => 'Your streak of ' . $streak . ' has ended.',
    ]);

    if ($notification->save()) {
      $this->log($userID, 'Create streak end notification ' . $notification->id . ' - success');
      return true;
    } else {
      $this->log($userID, 'Create streak end notification - failed');
      return false;
    }
  }
}
