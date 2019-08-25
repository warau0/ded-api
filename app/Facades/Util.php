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
    // Prepend timestamp and remove non alphanumeric characters
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

  public static function logLine($system, $userID, $msg) {
    $user = 'Guest';
    if ($userID === -1) {
      $user = 'System';
    } else if ($userID > 0) {
      $user = 'User ' . $userID;
    }

    Log::info('[' . str_pad($system, 3, '0', STR_PAD_LEFT) . ']'
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

  public static function sendPasswordReset($user, $passwordReset) {
    if (!$user->email) {
      return 'This user has no email registered.';
    }

    $url = "https://justdraw.lol/reset_password?user=" . $user->id . "&token=" . $passwordReset->token;

    $email = new \SendGrid\Mail\Mail();
    $email->setFrom("no-reply@justdraw.lol", "Draw Every Day");
    $email->setSubject("Password reset");
    $email->addTo($user->email, $user->username);
    $email->addContent("text/plain", "A password reset request was just made for your account. " .
      "If this was you, go to the following link to reset your password: " . $url .
      '. If you did not request a password reset, please disregard this email.');

    $email->addContent("text/html", '
      <!DOCTYPE html>
      <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

        <style type="text/css" rel="stylesheet" media="all">
          .body {
            font-family: Arial, Helvetica Neue, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            width: 100%;
            background-color: #F2F4F6;
          }

          .email-wrapper {
            width: 100%;
            margin: 0;
            padding: 0;
            background-color: #F2F4F6;
          }

          .email-masthead {
            padding: 25px 0;
            text-align: center;
          }

          .email-masthead_name {
            font-size: 16px;
            font-weight: bold;
            color: #2F3133;
            text-decoration: none;
            text-shadow: 0 1px 0 white;
          }

          .email-body {
            width: 100%;
            margin: 0;
            padding: 0;
            border-top: 1px solid #EDEFF2;
            border-bottom: 1px solid #EDEFF2;
            background-color: #FFF;
          }

          .email-body_inner {
            width: auto;
            max-width: 570px;
            margin: 0 auto;
            padding: 0;
          }

          .email-body_cell {
            padding: 35px;
          }

          .email-footer {
            width: auto;
            max-width: 570px;
            margin: 0 auto;
            padding: 10px;
            text-align: center;
          }

          .email-footer_cell {
            color: #AEAEAE;
            padding: 35px;
            text-align: center;
          }

          .body_action {
            width: 100%;
            margin: 30px auto;
            padding: 0;
            text-align: center;
          }

          .header-1 {
            margin-top: 0;
            color: #2F3133;
            font-size: 19px;
            font-weight: bold;
            text-align: left;
          }

          .paragraph {
            margin-top: 0;
            color: #74787E;
            font-size: 16px;
            line-height: 1.5em;
          }

          .paragraph-sub {
            margin-top: 0;
            color: #74787E;
            font-size: 12px;
            line-height: 1.5em;
          }

          .button {
            display: inline-block;
            width: 200px;
            min-height: 20px;
            padding: 10px;
            border-radius: 3px;
            color: #FFFFFF !important;
            font-size: 15px;
            line-height: 25px;
            text-align: center;
            text-decoration: none;
            -webkit-text-size-adjust: none;
            background-color: #3869D4;
          }
        </style>
      </head>
      <body>
        <div class="body">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td class="email-wrapper" align="center">
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="email-masthead">
                      <a class="email-masthead_name" href="https://justdraw.lol" target="_blank">
                        Draw Every Day
                      </a>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <td class="email-body" width="100%">
                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="email-body_cell">
                      <h1 class="header-1">
                        Hello ' . $user->username . ',
                      </h1>

                      <p class="paragraph">
                        A password reset request was just made for your account. If this was you, use the button below to set a new password.
                      </p>

                      <table class="body_action" align="center" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                          <td align="center">
                            <a href="' . $url . '" class="button" target="_blank">
                              Reset password
                            </a>
                          </td>
                        </tr>
                      </table>

                      <p class="paragraph">
                        If the button above does not work, copy and paste the following URL into your browser: ' . $url . '
                      </p>

                      <p class="paragraph">
                        Regards,<br/>W.
                      </p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <tr>
              <td>
                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="email-footer_cell>
                      <p class="paragraph-sub">
                        If you did not request a password reset, please disregard this email.
                      </p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </div>
      </body>
      </html>
    ');

    $sendgrid = new \SendGrid(env('SENDGRID_API_KEY'));
    try {
      $response = $sendgrid->send($email);
      return null;
    } catch (Exception $e) {
      return $e->getMessage();
    }
  }

  public static function connectToSpace() {
    $key = env('SPACES_KEY');
    $secret = env('SPACES_SECRET');
    $name = env('SPACES_NAME');
    $region = env('SPACES_REGION');

    return new \SpacesConnect($key, $secret, $name, $region);
  }

  public static function replaceCDN($url) {
    $cdn = env('CDN_URL');
    if (!$cdn) return $url;
    return preg_replace('/https?:\/\/.+\.com/', $cdn, $url);
  }
}