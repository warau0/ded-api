<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Tag;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubmissionController extends Controller {
  public function index(Request $request) {
    $submissions = Submission::query()->with('tags')->get();
    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function update(Request $request) {

  }

  public function store(Request $request) {
    $this->validate($request, [
      'description' => 'sometimes|string|max:1000|nullable',
      'hours' => 'sometimes|numeric|nullable',
      'nsfw' => 'sometimes|boolean|nullable',
      'private' => 'sometimes|boolean|nullable',
      'tags.*.value' => 'required|string',
      'tags.*.id' => 'sometimes|integer|nullable',
    ]);

    $user = $request->user;

    $submission = new Submission([
        'user_id' => $user->id,
        'description' => $request->input('description', ''),
        'hours' => $request->input('hours', '0.0'),
        'nsfw' => $request->input('nsfw', false),
        'private' => $request->input('private', false),
    ]);

    // Create unknown tags
    $tagIDs = [];
    foreach ($request->input('tags', []) as $tagInput) {
      if (!isset($tagInput['id'])) {
        $tag = new Tag([
          'user_id' => $user->id,
          'text' => $tagInput['value'],
        ]);

        if ($tag->save()) {
          array_push($tagIDs, $tag->id);
        }
      } else {
        array_push($tagIDs, $tagInput['id']);
      }
    }
    // Upload images

    if ($submission->save()) {
      $submission->tags()->sync($tagIDs); // Attach tags

      return response()->json($submission, Response::HTTP_OK);
    } else {
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request) {
    
  }
}