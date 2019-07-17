<?php

namespace App\Traits;

use App\Notification;
use App\Submission;
use App\Facades\Util;

trait Notifies {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.NOTIFICATION'), $code, $userID, $msg);
  }

  public function createSubmissionNotification($text, $userID, $submissionID) {
    $notification = new Notification([
      'user_id' => $userID,
      'text' => $text,
      'notification_parent_id' => $submissionID,
      'notification_parent_type' => Submission::class,
    ]);

    if ($notification->save()) {
      $this->log(1, $userID, 'Create notification ' . $notification->id . ' - success');
      return true;
    } else {
      $this->log(2, $userID, 'Create notification - failed');
      return false;
    }
  }
}
