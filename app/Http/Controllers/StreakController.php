<?php

namespace App\Http\Controllers;

use App\Streak;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Carbon\Carbon;

class StreakController extends Controller {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.STREAK'), $code, $userID, $msg);
  }

  public function show(Request $request) {
    $user = $request->user;
    $streak = Streak::query()
      ->where('user_id', $user->id)
      ->whereNull('end')
      ->first();

    return response()->json(['streak' => $streak], Response::HTTP_OK);
  }

  // Find and expire all streaks past their update window.
  public function endExpired() {
    $streaks = Streak::query()
      ->whereRaw('DATE(updated_at) <= DATE_SUB(UTC_DATE(), INTERVAL frequency + 1 day) AND count != 0 AND end IS NULL')
      ->get();

    $count = 0;
    foreach ($streaks as $key => $streak) {
      $streak->end = Carbon::now();
      if ($streak->save()) {
        $count++;
        $this->log(8, -1, 'Ending streak ' . $streak->id . ' of ' . $streak->count .' - success');
      } else {
        $this->log(9, -1, 'Ending streak ' . $streak->id . ' of ' . $streak->count .' - failed');
      }
    }

    if ($count === 0) {
      $this->log(10, -1, 'Ending streaks - no expired');
    }

    return response()->json(['ended' => $count], Response::HTTP_OK);
  }
}
