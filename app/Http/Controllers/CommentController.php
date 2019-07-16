<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Submission;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.COMMENT'), $code, $userID, $msg);
  }

  public function showSubmission(Request $request, $id) {
    $comments = Comment::query()
      ->with(['comments', 'user'])
      ->where([
        ['comment_parent_id', '=', $id],
        ['comment_parent_type', '=', Submission::class],
      ])
      ->orderBy('created_at', 'desc')
      ->get();

    return response()->json(['comments' => $comments], Response::HTTP_OK);
  }

  public function storeSubmission(Request $request, $id) {
    $this->validate($request, [
      'anonymous' => 'sometimes|boolean',
      'text' => 'required|string|max:5000',
    ]);

    $user = $request->user;

    $comment = new Comment([
      'user_id' => $user->id,
      'anonymous' => $request->input('anonymous', false),
      'text' => $request->input('text', ''),
      'comment_parent_id' => $id,
      'comment_parent_type' => Submission::class,
    ]);

    if ($comment->save()) {
      $this->log(1, $user->id, 'Save comment ' . $comment->id . ' - success');
      return response()->json($comment, Response::HTTP_OK);
    } else {
      $this->log(2, $user->id, 'Save comment - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
