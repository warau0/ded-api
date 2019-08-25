<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.TAG'), $userID, $msg);
  }

  public function index(Request $request) {
    $user = $request->user;
    $tags = Tag::where('user_id', $user->id)->get();
    return response()->json(['tags' => $tags], Response::HTTP_OK);
  }

  public function update(Request $request, $id) {
    $this->validate($request, [
      'color' => 'required|string',
      'text' => 'required|string',
    ]);

    $tag = Tag::find($id);
    $user = $request->user;

    if (!$tag) {
      $this->log($tag->user_id, 'Update tag ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $tag->user_id) {
      $this->log($tag->user_id, 'Update tag ' . $id . ' - forbidden');
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $updateResult = $tag->update([
      'color' => $request->input('color'),
      'text' => $request->input('text', ''),
    ]);

    if ($updateResult) {
      $this->log($tag->user_id, 'Update tag ' . $id . ' - success');
      return response()->json($tag, Response::HTTP_OK);
    } else {
      $this->log($tag->user_id, 'Update tag ' . $id . ' - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function store(Request $request) {
    $this->validate($request, [
      'color' => 'required|string',
      'text' => 'required|string',
    ]);

    $user = $request->user;

    $tag = new Tag([
        'user_id' => $user->id,
        'color' => $request->input('color'),
        'text' => $request->input('text', ''),
    ]);

    if ($tag->save()) {
      $this->log($tag->user_id, 'Create tag ' . $tag->id . ' - success');
      return response()->json($tag, Response::HTTP_OK);
    } else {
      $this->log($tag->user_id, 'Create tag - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request, $id) {
    $tag = Tag::find($id);
    $user = $request->user;

    if (!$tag) {
      $this->log($tag->user_id, 'Delete tag ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $tag->user_id) {
      $this->log($tag->user_id, 'Delete tag ' . $id . ' - forbidden');
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $result = $tag->delete();

    if ($result) {
      $this->log($tag->user_id, 'Delete tag ' . $id . ' - success');
      return response()->json($result, Response::HTTP_OK);
    } else {
      $this->log($tag->user_id, 'Delete tag ' . $id . ' - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
