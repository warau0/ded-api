<?php

namespace App\Http\Controllers;

use App\SocialLink;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SocialLinkController extends Controller {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.SOCIAL_LINK'), $userID, $msg);
  }

  public function store(Request $request) {
    $this->validate($request, [
      'link' => 'required|string',
    ]);

    $user = $request->user;

    $match = preg_match('/^https?:\/\/.*/', $request->input('link'));

    if (!$match) {
      $this->log($user->id, 'Create social link - invalid link');
      return response()->json(['error' => 'Invalid link, make sure it starts with http or https.'], Response::HTTP_BAD_REQUEST);
    }

    $socialLinks = SocialLink::where('user_id', $user->id)->count();

    if ($socialLinks >= 5) {
      $this->log($user->id, 'Create social link - too many links');
      return response()->json(['error' => 'You already have the maximum of 5 social links.'], Response::HTTP_BAD_REQUEST);
    }

    $socialLink = new SocialLink([
      'user_id' => $user->id,
      'link' => $request->input('link'),
    ]);

    if ($socialLink->save()) {
      $this->log($socialLink->user_id, 'Create social link ' . $socialLink->id . ' - success');
      return response()->json($socialLink, Response::HTTP_OK);
    } else {
      $this->log($socialLink->user_id, 'Create social link - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request, $id) {
    $socialLink = SocialLink::find($id);
    $user = $request->user;

    if (!$socialLink) {
      $this->log($user->id, 'Delete social link ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $socialLink->user_id) {
      $this->log($user->id, 'Delete social link ' . $id . ' - forbidden');
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $result = $socialLink->delete();

    if ($result) {
      $this->log($socialLink->user_id, 'Delete social link ' . $id . ' - success');
      return response()->json($result, Response::HTTP_OK);
    } else {
      $this->log($socialLink->user_id, 'Delete social link ' . $id . ' - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
