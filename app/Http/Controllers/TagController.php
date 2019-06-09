<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TagController extends Controller {
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
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $tag->user_id) {
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $updateResult = $tag->update([
      'color' => $request->input('color'),
      'text' => $request->input('text', ''),
    ]);

    if ($updateResult) {
        return response()->json($tag, Response::HTTP_OK);
    } else {
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
      return response()->json($tag, Response::HTTP_OK);
    } else {
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request, $id) {
    $tag = Tag::find($id);
    $user = $request->user;

    if (!$tag) {
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $tag->user_id) {
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $result = $tag->delete();

    if ($result) {
      return response()->json($result, Response::HTTP_OK);
    } else {
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
