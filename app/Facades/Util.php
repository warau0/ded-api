<?php

namespace App\Facades;

use App\Tag;
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
    Log::info('[' . str_pad($system, 3, '0', STR_PAD_LEFT) . '.' . str_pad($code, 3, '0', STR_PAD_LEFT) . ']'
      . ($userID ? ' (User '. $userID .') ' : ' (Guest) ')
      . $msg
    );
  }
}