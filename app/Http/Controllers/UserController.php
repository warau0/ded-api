<?php

namespace App\Http\Controllers;

use App\Submission;
use App\User;
use App\Image;
use App\Facades\Util;
use App\Traits\SavesImages;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\ImageManager;

class UserController extends Controller {
  use SavesImages;

  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.USER'), $userID, $msg);
  }

  public function show(Request $request, $id) {
    $user = User::query()
      ->where('username',  urldecode($id))
      ->orWhere('id', $id)
      ->with('avatar')
      ->first();

    if (!$user) {
      $loggedInUser = $request->user;
      $this->log($loggedInUser ? $loggedInUser->id : null, 'Show profile ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    return response()->json(['user' => $user], Response::HTTP_OK);
  }

  public function submissions(Request $request, $id) {
    $user = User::query()
      ->where('username',  urldecode($id))
      ->orWhere('id', $id)
      ->first();

    if (!$user) {
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    $loggedInUser = $request->user;
    if ($loggedInUser && $user->id === $loggedInUser->id) {
      $where = [
        ['user_id', '=', $user->id]
      ];
    } else {
      $where = [
        ['private', '=', false],
        ['user_id', '=', $user->id]
      ];
    }

    $submissions = Submission::query()
      ->with(['tags', 'images.thumbnail'])
      ->orderBy('id', 'desc')
      ->where($where)
      ->whereHas('images')
      ->simplePaginate(40);

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function updateAvatar(Request $request) {
    $user = $request->user;
    $image = $request->avatar;

    if (!$request->input('has_data', false)) { // Requests that exceed post_max_size will trigger this.
      $this->log($user->id, 'Update avatar - max post size exceeded');
      return response()->json(['avatar' => 'Image too large (3 MB).'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (!isset($image)) {
      $this->log($user->id, 'Update avatar - no image');
      return response()->json(['avatar' => 'No image selected.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $manager = new ImageManager(array('driver' => 'gd'));
    $space = Util::connectToSpace();
    $imageResult = $this->saveImage($manager, $space, $image, $user->id, 'avatars', User::class, $user->id, 100);

    if ($imageResult['error']) {
      $this->log($user->id, 'Update avatar - ' . $imageResult['error']);
      return response()->json(['avatar' => $imageResult['error']], Response::HTTP_UNPROCESSABLE_ENTITY);
    } else {
      $deleteResult = Image::where([
        ['image_parent_id', '=', $user->id],
        ['image_parent_type', '=', User::class],
        ['id', '!=', $imageResult['image']->id]
      ])->delete(); // Delete any old avatars.

      // TODO Delete old avatar in space.

      $this->log($user->id, 'Update avatar ' . $imageResult['image']->id . ' - success');
      if ($deleteResult) {
        $this->log($user->id, 'Delete old avatars: ' . $deleteResult);
      }
      return response()->json(['avatar' => $imageResult['image']], Response::HTTP_OK);
    }
  }

  public function getAvatar(Request $request) {
    $avatar = Image::query()->where([
      ['image_parent_id', '=', $request->user->id],
      ['image_parent_type', '=', User::class],
    ])->first();

    return response()->json(['avatar' => $avatar], Response::HTTP_OK);
  }
}