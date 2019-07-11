<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Tag;
use App\Image;
use App\Streak;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Intervention\Image\Exception\NotWritableException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Carbon\Carbon;

class SubmissionController extends Controller {
  private function log($code, $userID, $msg) {
    Util::logLine(config('constants.LOG.SUBMISSION'), $code, $userID, $msg);
  }

  public function index(Request $request) {
    $submissions = Submission::query()
      ->with(['tags', 'images.thumbnail'])
      ->limit(48)
      ->orderBy('id', 'desc')
      ->where('private', false)
      ->whereHas('images')
      ->get();

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function show(Request $request, $id) {
    $submission = Submission::query()
    ->with(['tags', 'images', 'user.avatar'])
    ->where('private', false)
    ->find($id);

    $user = $request->user;

    if (!$submission) {
      $this->log(15, $user ? $user->id : null, 'Show submission ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    return response()->json(['submission' => $submission], Response::HTTP_OK);
  }

  public function update(Request $request) {
    // TODO
  }

  public function store(Request $request) {
    $this->validate($request, [
      'description' => 'sometimes|string|max:1000|nullable',
      'hours' => 'sometimes|numeric|nullable',
      'nsfw' => 'sometimes|numeric|nullable|in:1,0',
      'private' => 'sometimes|numeric|nullable|in:1,0',
      'tags.*.value' => 'required|string',
      'tags.*.id' => 'sometimes|integer|nullable',
    ]);

    if (!$request->images) {
      $this->log(1, $user->id, 'Save submission - no images');
      return response()->json(['images' => 'You have to upload at least one image.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

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
    $tags = json_decode($request->input('tags', []));
    foreach ($tags as $tagInput) {
      $tagID = Util::findExistingTagID($tagInput, $user->id);
      if (!$tagID) {
        $tag = new Tag([
          'user_id' => $user->id,
          'text' => $tagInput->value,
        ]);
        $tag->save();
        $this->log(2, $user->id, 'Save submission - Create tag ' . $tag->id);
        $tagID = $tag->id;
      }
      array_push($tagIDs, $tagID);
    }

    if ($submission->save()) {
      $submission->tags()->sync($tagIDs); // Attach tags

      $manager = new ImageManager(array('driver' => 'gd'));

      // Upload images
      foreach ($request->images as $image) {
        try {
          $interventionImage = $manager->make($image);
          $hash = Util::imageHash($interventionImage);
        } catch(NotReadableException $e) {
          $submission->images()->delete();
          $submission->delete();
          $this->log(3, $user->id, 'Save submission ' . $submission->id . ' - not readable image');
          return response()->json(['images' => 'Damaged or too big (5 MB) image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filename = Util::imageName($image->getClientOriginalName());
        $path = 'public/images/' . $user->id . '/original/';

        $existingImage = Image::where([
          ['size', '=', $image->getSize()],
          ['hash', '=', $hash]
        ])->first();
        if ($existingImage) {
          $submission->images()->delete();
          $submission->delete();
          $this->log(4, $user->id, 'Save submission ' . $submission->id . ' - duplicate image');
          return response()->json(['images' => 'Duplicate image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!Util::imageValidMime($interventionImage)) {
          $submission->images()->delete();
          $submission->delete();
          $this->log(5, $user->id, 'Save submission ' . $submission->id . ' - invalid mime');
          return response()->json(['images' => 'Not a jpeg, png or gif image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $image->storeAs($path, $filename);
        $this->log(6, $user->id, 'Save submission ' . $submission->id . ' - save image ' . $path . $filename);
        $imageModel = new Image([
          'name' => $filename,
          'path' => $path,
          'hash' => $hash,
          'size' => $image->getSize(),
          'height' => $interventionImage->height(),
          'width' => $interventionImage->width(),
          'mime' => $interventionImage->mime(),
          'image_parent_id' => $submission->id,
          'image_parent_type' => Submission::class,
        ]);

        if ($imageModel->save()) {
          try {
            // Transparent parts of gif turn blue, png turn black
            $interventionImage->fit(250, 250, function ($constraint) {
                $constraint->upsize();
            })->encode('jpg', 75);
          } catch(Exception $e) {
            $this->log(7, $user->id, 'Save submission ' . $submission->id . ' - thumbnailing failed for image ' . $imageModel->id);
            return [
              'error' => 'Failed generating thumbnail, submission failed.',
              'error_code' => Response::HTTP_INTERNAL_SERVER_ERROR
            ];
          }

          $thumbnailName = $filename;
          $thumbnailFolder = storage_path('app/public/images/' . $user->id . '/thumbnails/');

          if (!File::isDirectory($thumbnailFolder)) {
            $this->log(8, $user->id, 'Save submission ' . $submission->id . ' - create folder ' . $thumbnailFolder);
            File::makeDirectory($thumbnailFolder, 0755, true);
          }

          try {
            $interventionImage->save($thumbnailFolder . $thumbnailName);
            $this->log(9, $user->id, 'Save submission ' . $submission->id . ' - save thumbnail ' . $thumbnailFolder . $thumbnailName);
          } catch (NotWritableException $ex) {
              $submission->images()->delete();
              $submission->delete();
              $this->log(10, $user->id, 'Save submission ' . $submission->id . ' - not writable thumbnail for image ' . $imageModel->id);
              return [
                  'error' => 'Could not save thumbnail.',
                  'error_code', Response::HTTP_INTERNAL_SERVER_ERROR
              ];
          }

          $thumbSize = $interventionImage->getSize();
          $thumbnailModel = new Image([
            'name' => $filename,
            'path' => $path,
            'hash' => Util::imageHash($interventionImage),
            'size' => 0,
            'height' => $interventionImage->height(),
            'width' => $interventionImage->width(),
            'mime' => $interventionImage->mime(),
            'image_parent_id' => $imageModel->id,
            'image_parent_type' => Image::class,
          ]);

          if (!$thumbnailModel->save()) {
            $submission->images()->delete();
            $submission->delete();
            $this->log(11, $user->id, 'Save submission ' . $submission->id . ' - failed saving thumbnail for image ' . $imageModel->id);
            return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
          }
        } else {
          $this->log(12, $user->id, 'Save submission ' . $submission->id . ' - failed saving image');
          return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
      }

      // Bump streak
      $activeStreak = Streak::query()
        ->where('user_id', $user->id)
        ->whereNull('end')
        ->first();
      if ($activeStreak) {
        $activeStreak->attemptBump();
      } else {
        $previousStreak = Streak::query()->where('user_id', $user->id)->orderBy('id', 'desc')->first();

        $streak = new Streak();
        $streak->count = 1;
        $streak->user_id = $user->id;
        $streak->frequency = $previousStreak ? $previousStreak->frequency : $request->input('frequency', 1);
        if ($streak->save()) {
          Util::logLine(config('constants.LOG.STREAK'), 1, $user->id, 'Create streak ' . $streak->id . ' - success');
        } else {
          Util::logLine(config('constants.LOG.STREAK'), 2, $user->id, 'Create streak - failed');
        }
      }

      $this->log(13, $user->id, 'Save submission ' . $submission->id . ' - success');
      return response()->json($submission, Response::HTTP_OK);
    } else {
      $this->log(14, $user->id, 'Save submission - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request) {
    // TODO
  }

  public function monthlyLeaderboard(Request $request) {
    $time = Carbon::now()->subMonths($request->input('offset', 0));
    $period = $time->year . '-'.  str_pad($time->month,  2, '0', STR_PAD_LEFT);

    $leaderboard = Submission::query()
      ->select('users.id', 'users.username')
      ->selectRaw('SUM(hours) as total_hours, DATE_FORMAT(submissions.created_at, \'%Y-%m\') as time_period') // 2019-07
      ->having('time_period', '=', $period)
      ->join('users', 'users.id', 'submissions.user_id')
      ->groupBy('users.id', 'users.username', 'time_period')
      ->orderBy('total_hours', 'desc')
      ->get();

    return response()->json(['leaderboard' => $leaderboard], Response::HTTP_OK);
  }
}