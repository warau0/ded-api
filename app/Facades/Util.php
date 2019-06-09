<?php

namespace App\Facades;

use App\Tag;

class Util {
  public static function saveImage($image) {

  }

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
}