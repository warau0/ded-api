<?php

namespace App\Http\Controllers;

use App\Submission;
use App\User;
use App\Image;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Intervention\Image\Exception\NotWritableException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;

class UserController extends Controller {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.USER'), $code, $userID, $msg);
  }

  public function show(Request $request, $id) {
    $user = User::query()
      ->where('username',  $id)
      ->orWhere('id', $id)
      ->with('avatar')
      ->first();

    if (!$user) {
      $loggedInUser = $request->user;
      $this->log(6, $loggedInUser ? $loggedInUser->id : null, 'Show profile ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    return response()->json(['user' => $user], Response::HTTP_OK);
  }

  public function submissions(Request $request, $id) {
    $user = User::query()
      ->where('username',  $id)
      ->orWhere('id', $id)
      ->first();

    if (!$user) {
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    $submissions = Submission::query()
      ->with(['tags', 'images.thumbnail'])
      ->limit(48)
      ->orderBy('id', 'desc')
      ->where([
        ['private', '=', false],
        ['user_id', '=', $user->id]
      ])
      ->whereHas('images')
      ->get();

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function updateAvatar(Request $request) {
    $user = $request->user;
    $image = $request->avatar;
    if (!isset($image)) {
      $this->log(7, $user->id, 'Update avatar - no image');
      return response()->json(['avatar' => 'No image selected.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $manager = new ImageManager(array('driver' => 'gd'));

    try {
      $interventionImage = $manager->make($image);
      $hash = Util::imageHash($interventionImage);
    } catch(NotReadableException $e) {
      $this->log(8, $user->id, 'Update avatar - not readable image');
      return response()->json(['avatar' => 'Damaged or too big (5 MB) image.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $filename = Util::imageName($image->getClientOriginalName());
    $path = storage_path('app/public/images/' . $user->id . '/avatar/');

    if (!Util::imageValidMime($interventionImage)) {
      $this->log(9, $user->id, 'Update avatar - invalid mime');
      return response()->json(['avatar' => 'Not a jpeg, png or gif image.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
      // Transparent parts of gif turn blue, png turn black
      $interventionImage->fit(100, 100, function ($constraint) {
          $constraint->upsize();
      })->encode('jpg', 75);
    } catch(Exception $e) {
      $this->log(10, $user->id, 'Update avatar - encoding failed');
      return response()->json(['error' => 'Failed generating thumbnail.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    if (!File::isDirectory($path)) {
      $this->log(11, $user->id, 'Update avatar - create folder ' . $path);
      File::makeDirectory($path, 0755, true);
    }

    try {
      $interventionImage->save($path . $filename);
      $this->log(12, $user->id, 'Update avatar - save image ' . $path . $filename);
    } catch (NotWritableException $ex) {
        $this->log(13, $user->id, 'Update avatar - not writable image');
        return response()->json(['error' => 'Could not save thumbnail.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    $imageModel = new Image([
      'name' => $filename,
      'path' => $path,
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

      $this->log(14, $user->id, 'Update avatar ' . $imageModel->id . ' - success');
      if ($deleteResult) {
        $this->log(14, $user->id, 'Delete old avatars: ' . $deleteResult);
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