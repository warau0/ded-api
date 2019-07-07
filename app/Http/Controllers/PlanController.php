<?php

namespace App\Http\Controllers;

use App\Plan;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlanController extends Controller {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.PLAN'), $code, $userID, $msg);
  }

  protected function checkForOverlap($id, $userID, $day, $hour, $duration) {
    $hours = [];

    for ($i = $hour; $i < ($hour + $duration); $i++) {
      array_push($hours, $i);
    }

    $plans = Plan::query()
    ->where([
      ['user_id', $userID],
      ['id', '!=', $id],
      ['day', $day]
    ])
    ->whereIn('start', $hours)
    ->get();

    return sizeof($plans) > 0;
  }

  public function index(Request $request) {
    $user = $request->user;
    $plans = Plan::where('user_id', $user->id)->get();

    $planIndex = [];
    foreach ($plans as $plan) {
      if(!isset($planIndex[$plan->day])) $planIndex[$plan->day] = [];
      $planIndex[$plan->day][$plan->start] = $plan;
    }

    return response()->json(['plans' => $planIndex], Response::HTTP_OK);
  }

  public function update(Request $request, $id) {
    $this->validate($request, [
      'day' => 'required|integer|min:0|max:6',
      'start' => 'required|integer|min:0|max:23',
      'duration' => 'required|integer|min:1|max:24',
      'color' => 'required|string',
      'text' => 'sometimes|string|nullable',
    ]);

    $plan = Plan::find($id);
    $user = $request->user;

    if (!$plan) {
      $this->log(1, $user->id, 'Update plan ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $plan->user_id) {
      $this->log(2, $user->id, 'Update plan ' . $id . ' - forbidden');
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $day = intval($request->input('day'));
    $start = intval($request->input('start'));
    $duration = intval($request->input('duration'));

    if ($this->checkForOverlap($id, $user->id, $day, $start, $duration)) {
      $this->log(3, $user->id, 'Update plan ' . $id . ' - period overlap');
      return response()->json(['error' => 'Another plan overlaps this period.'], Response::HTTP_BAD_REQUEST);
    }

    $updateResult = $plan->update([
      'day' => $day,
      'start' => $start,
      'duration' => $duration,
      'color' => $request->input('color'),
      'text' => $request->input('text', ''),
    ]);

    if ($updateResult) {
      $this->log(4, $user->id, 'Update plan ' . $id . ' - success');
      return response()->json($plan->fresh(), Response::HTTP_OK);
    } else {
      $this->log(5, $user->id, 'Update plan ' . $id . ' - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function store(Request $request) {
    $this->validate($request, [
      'day' => 'required|integer|min:0|max:6',
      'start' => 'required|integer|min:0|max:23',
      'duration' => 'required|integer|min:1|max:24',
      'color' => 'required|string',
      'text' => 'sometimes|string|nullable',
    ]);

    $user = $request->user;

    $day = intval($request->input('day'));
    $start = intval($request->input('start'));
    $duration = intval($request->input('duration'));

    if ($this->checkForOverlap(null, $user->id, $day, $start, $duration)) {
      $this->log(6, $user->id, 'Save plan - period overlap');
      return response()->json(['error' => 'Another plan overlaps this period.'], Response::HTTP_BAD_REQUEST);
    }

    $plan = new Plan([
        'user_id' => $user->id,
        'day' => $day,
        'start' => $start,
        'duration' => $duration,
        'color' => $request->input('color'),
        'text' => $request->input('text', ''),
    ]);

    if ($plan->save()) {
      $this->log(7, $user->id, 'Save plan ' . $plan->id . ' - success');
      return response()->json($plan, Response::HTTP_OK);
    } else {
      $this->log(8, $user->id, 'Save plan - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request, $id) {
    $plan = Plan::find($id);
    $user = $request->user;

    if (!$plan) {
      $this->log(9, $user->id, 'Delete plan ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $plan->user_id) {
      $this->log(10, $user->id, 'Delete plan ' . $id . ' - forbidden');
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $result = $plan->delete();

    if ($result) {
      $this->log(11, $user->id, 'Delete plan ' . $id . ' - success');
      return response()->json($result, Response::HTTP_OK);
    } else {
      $this->log(12, $user->id, 'Delete plan ' . $id . ' - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
