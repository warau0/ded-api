<?php

namespace App\Http\Controllers;

use App\Submission;
use App\User;
use App\Image;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Exception\NotWritableException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;

class UserController extends Controller {
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
      ->limit(48)
      ->orderBy('id', 'desc')
      ->where($where)
      ->whereHas('images')
      ->get();

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function updateAvatar(Request $request) {
    $user = $request->user;
    $image = $request->avatar;

    if (!$request->input('has_data', false)) { // Requests that exceed post_max_size will trigger this.
      $this->log($user->id, 'Update avatar - max post size exceeded');
      return response()->json(['images' => 'Image too large (3 MB).'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (!isset($image)) {
      $this->log($user->id, 'Update avatar - no image');
      return response()->json(['avatar' => 'No image selected.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $manager = new ImageManager(array('driver' => 'gd'));

    try {
      $interventionImage = $manager->make($image);
      $hash = Util::imageHash($interventionImage);
    } catch(NotReadableException $e) { // Single files that exceed upload_max_filesize will trigger this.
      $this->log($user->id, 'Update avatar - not readable image');
      return response()->json(['images' => 'Image too large (3 MB) or damaged.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if (!Util::imageValidMime($interventionImage)) {
      $this->log($user->id, 'Update avatar - invalid mime');
      return response()->json(['avatar' => 'Not a jpeg, png or gif image.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
      // Transparent parts of gif turn blue, png turn black
      $interventionImage->fit(100, 100, function ($constraint) {
          $constraint->upsize();
      })->encode('jpg');
    } catch(Exception $e) {
      $this->log($user->id, 'Update avatar - encoding failed');
      return response()->json(['error' => 'Failed generating thumbnail.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $fullPath = env('SPACES_FOLDER') . '/' . $user->id . '/avatars/'
      . Util::imageName($image->getClientOriginalName());

    $space = Util::connectToSpace();
    $response = $space->UploadFile($interventionImage->stream(), 'public', $fullPath, $interventionImage->mime());
    // TODO Handle failed upload. $this->log($user->id, 'Update avatar - not writable image');
    $this->log($user->id, 'Update avatar - save image ' . $fullPath);

    $imageModel = new Image([
      'file' => $fullPath,
      'url' => Util::replaceCDN($response['ObjectURL']),
      'hash' => $hash,
      'size' => $interventionImage->filesize(),
      'height' => $interventionImage->height(),
      'width' => $interventionImage->width(),
      'mime' => $interventionImage->mime(),
      'image_parent_id' => $user->id,
      'image_parent_type' => User::class,
    ]);

    if ($imageModel->save()) {
      $deleteResult = Image::where([
        ['image_parent_id', '=', $user->id],
        ['image_parent_type', '=', User::class],
        ['id', '!=', $imageModel->id]
      ])->delete(); // Delete any old avatars.

      // TODO Delete old avatar in space.

      $this->log($user->id, 'Update avatar ' . $imageModel->id . ' - success');
      if ($deleteResult) {
        $this->log($user->id, 'Delete old avatars: ' . $deleteResult);
      }
      return response()->json(['avatar' => $imageModel], Response::HTTP_OK);
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