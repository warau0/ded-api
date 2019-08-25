<?php

namespace App\Http\Controllers;

use App\Notification;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificationController extends Controller {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.COMMENT'), $userID, $msg);
  }

  public function index(Request $request) {
    $notifications = Notification::query()
      ->where('user_id', $request->user->id)
      ->orderBy('seen', 'asc')
      ->orderBy('created_at', 'desc')
      ->limit(10)
      ->get();

    return response()->json(['notifications' => $notifications], Response::HTTP_OK);
  }

  public function seen(Request $request) {
    $seen = Notification::query()
      ->where([
        ['user_id', '=', $request->user->id],
        ['seen', '=', false],
      ])
      ->update(['seen' => true]);

    return response()->json(['seen' => $seen], Response::HTTP_OK);
  }
}
