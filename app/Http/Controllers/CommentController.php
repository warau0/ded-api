<?php

namespace App\Http\Controllers;

use App\Comment;
use App\Submission;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use App\Traits\Notifies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller {
  use Notifies;

  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.COMMENT'), $userID, $msg);
  }

  public function showSubmission(Request $request, $id) {
    $comments = Comment::query()
      ->with(['comments', 'user.avatar'])
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
    $anonymous = $request->input('anonymous', false);
    $text = $request->input('text', '');

    $comment = new Comment([
      'user_id' => $user->id,
      'anonymous' => $anonymous,
      'text' => $text,
      'comment_parent_id' => $id,
      'comment_parent_type' => Submission::class,
    ]);

    if ($comment->save()) {
      $this->log($user->id, 'Create comment ' . $comment->id . ' - success');

      $submission = Submission::find($id);
      if ($submission->user_id !== $user->id) {
        $notification = ($anonymous ? 'Someone' : $user->username) . ' left you a comment: ' . Util::trimText($text);
        $this->createSubmissionNotification($notification, $submission->user_id, $id);
      }

      return response()->json($comment, Response::HTTP_OK);
    } else {
      $this->log($user->id, 'Create comment - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function reply(Request $request, $id) {
    $this->validate($request, [
      'anonymous' => 'sometimes|boolean',
      'text' => 'required|string|max:5000',
    ]);

    $user = $request->user;

    $anonymous = $request->input('anonymous', false);
    $text = $request->input('text', '');

    $comment = new Comment([
      'user_id' => $user->id,
      'anonymous' => $anonymous,
      'text' => $text,
      'comment_parent_id' => $id,
      'comment_parent_type' => Comment::class,
    ]);

    if ($comment->save()) {
      $this->log($user->id, 'Create comment ' . $comment->id . ' - success');

      $parentComment = Comment::find($id);
      $submission = Util::findCommentParent($parentComment);
      if ($parentComment->user_id !== $user->id) {
        $notification = ($anonymous ? 'Someone' : $user->username) . ' replied to you: ' . Util::trimText($text);
        $this->createSubmissionNotification($notification, $parentComment->user_id, $submission->id);
      }
      if ($submission->user_id !== $user->id && $submission->user_id !== $parentComment->user_id) {
        $notification = ($anonymous ? 'Someone' : $user->username) . ' left you a comment: ' . Util::trimText($text);
        $this->createSubmissionNotification($notification, $submission->user_id, $submission->id);
      }

      return response()->json($comment, Response::HTTP_OK);
    } else {
      $this->log($user->id, 'Create comment - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function update(Request $request, $id) {
    $this->validate($request, [
      'text' => 'required|string|max:5000',
    ]);

    $comment = Comment::find($id);
    $user = $request->user;

    if (!$comment) {
      $this->log($user->id, 'Update comment ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($user->id !== $comment->user_id) {
      $this->log($user->id, 'Update comment ' . $id . ' - forbidden');
      return response()->json(['error' => 'No access.'], Response::HTTP_FORBIDDEN);
    }

    $updateResult = $comment->update([
      'text' => $request->input('text'),
    ]);

    if ($updateResult) {
      $this->log($user->id, 'Update comment ' . $id . ' - success');
      return response()->json($comment, Response::HTTP_OK);
    } else {
      $this->log($user->id, 'Update comment ' . $id . ' - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }
}
