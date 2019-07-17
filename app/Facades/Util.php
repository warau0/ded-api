<?php

namespace App\Facades;

use App\Tag;
use App\Comment;
use Illuminate\Support\Facades\Log;

class Util {
  public static function findExistingTagID($tagInput, $userID) {
    if (isset($tagInput->id)) return $tagInput->id;

    $existingTag = Tag::where([
      ['user_id', $userID],
      ['text', $tagInput->value]
    ])->first();

    if ($existingTag) return $existingTag->id;

    return null;
  }

  public static function replaceLast($search, $replace, $subject) {
    $pos = strrpos($subject, $search);
    if ($pos !== false) {
      $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
  }

  public static function imageName($originalName) {
    $filename = time() . '_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($originalName));
    $filename = self::replaceLast('_', '.', $filename);
    return $filename;
  }

  public static function imageHash($interventionImage) {
    return hash('sha256', $interventionImage->encode('data-url'));
  }

  public static function imageValidMime($interventionImage) {
    switch($interventionImage->mime()) {
      case 'image/png': return true;
      case 'image/jpeg': return true;
      case 'image/gif': return true;
    }
    return false;
  }

  public static function logLine($system, $code, $userID, $msg) {
    $user = 'Guest';
    if ($userID === -1) {
      $user = 'System';
    } else if ($userID > 0) {
      $user = 'User ' . $userID;
    }

    Log::info('[' . str_pad($system, 3, '0', STR_PAD_LEFT) . '.' . str_pad($code, 3, '0', STR_PAD_LEFT) . ']'
      . ' (' . $user . ') '
      . $msg
    );
  }

  public static function trimText($text, $length = 30) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
  }

  public static function findCommentParent($comment) {
    if ($comment->comment_parent_type === Comment::class) {
      // Parent is another comment, try find it's parent instead.
      $parentComment = Comment::find($comment->comment_parent_id);
      return Util::findCommentParent($parentComment);
    } else if ($comment->comment_parent_type) {
      // Parent is something else, return the model.
      return $comment->comment_parent_type::find($comment->comment_parent_id);
    } else {
      return $comment; // Has no parent.
    }
  }
}