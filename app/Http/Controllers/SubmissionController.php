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
use Intervention\Image\Exception\NotWritableException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Carbon\Carbon;

class SubmissionController extends Controller {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.SUBMISSION'), $userID, $msg);
  }

  public function index(Request $request) {
    $submissions = Submission::query()
      ->with('images.thumbnail')
      ->limit(48)
      ->orderBy('id', 'desc')
      ->where('private', false)
      ->whereHas('images')
      ->get();

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function show(Request $request, $id) {
    $submission = Submission::query()
      ->with(['tags', 'images', 'user.avatar', 'comments'])
      ->find($id);

    $user = $request->user;

    if (!$submission
      || (!$user && $submission->private)
      || ($user && $submission->private && $submission->user_id !== $user->id)
    ) {
      $this->log($user ? $user->id : null, 'Show submission ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    $nextSubmission = Submission::selectRaw('min(id) as id')
      ->where([['id', '>', $submission->id], ['private', '=', false]])->first();
    $nextUserSubmission = Submission::selectRaw('min(id) as id')
      ->where([['id', '>', $submission->id], ['user_id', '=', $submission->user_id], ['private', '=', false]])->first();

    $previousSubmission = Submission::selectRaw('max(id) as id')
      ->where([['id', '<', $submission->id], ['private', '=', false]])->first();
    $previousUserSubmission = Submission::selectRaw('max(id) as id')
      ->where([['id', '<', $submission->id], ['user_id', '=', $submission->user_id], ['private', '=', false]])->first();

    $randomUserSubmissions = Submission::query()
      ->with('images.thumbnail')
      ->where([
        ['user_id', '=', $submission->user_id],
        ['id', '!=', $submission->id],
        ['private', '=', false],
      ])
      ->inRandomOrder()
      ->limit(6)
      ->get();

    return response()->json([
      'submission' => $submission,
      'next_submission_id' => $nextSubmission ? $nextSubmission->id : null,
      'next_user_submission_id' => $nextUserSubmission ? $nextUserSubmission->id : null,
      'previous_submission_id' => $previousSubmission ? $previousSubmission->id : null,
      'previous_user_submission_id' => $previousUserSubmission ? $previousUserSubmission->id : null,
      'user_submissions' => $randomUserSubmissions,
    ], Response::HTTP_OK);
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

    $user = $request->user;

    if (!$request->images) {
      $this->log($user->id, 'Create submission - no images');
      return response()->json(['images' => 'You have to upload at least one image.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

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
        $this->log($user->id, 'Create submission - Create tag ' . $tag->id);
        $tagID = $tag->id;
      }
      array_push($tagIDs, $tagID);
    }

    if ($submission->save()) {
      $submission->tags()->sync($tagIDs); // Attach tags

      $manager = new ImageManager(array('driver' => 'gd'));
      $space = Util::connectToSpace();

      // Upload images
      foreach ($request->images as $image) {
        try {
          $interventionImage = $manager->make($image);
          $hash = Util::imageHash($interventionImage);
        } catch(NotReadableException $e) {
          $submission->images()->delete();
          $submission->delete();
          $this->log($user->id, 'Create submission ' . $submission->id . ' - not readable image');
          return response()->json(['images' => 'Damaged or too big (5 MB) image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!env('ALLOW_DUPLICATE_SUBMISSIONS')) {
          $existingImage = Image::where([
            ['size', '=', $image->getSize()],
            ['hash', '=', $hash],
            ['image_parent_type', '=', Submission::class],
          ])->first();
          if ($existingImage) {
            $submission->images()->delete();
            $submission->delete();
            $this->log($user->id, 'Create submission ' . $submission->id . ' - duplicate image');
            return response()->json(['images' => 'Duplicate image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
          }
        }

        if (!Util::imageValidMime($interventionImage)) {
          $submission->images()->delete();
          $submission->delete();
          $this->log($user->id, 'Create submission ' . $submission->id . ' - invalid mime');
          return response()->json(['images' => 'Not a jpeg, png or gif image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $imgName = Util::imageName($image->getClientOriginalName());

        $fullPath = env('SPACES_FOLDER') . '/' . $user->id . '/images/' . $imgName;

        // Use UploadedFile path to tmp upload file because intervention stream breaks gifs.
        $response = $space->UploadFile($image->path(), 'public', $fullPath, $interventionImage->mime());
        // TODO Handle failed upload.

        $this->log($user->id, 'Create submission ' . $submission->id . ' - save image ' . $fullPath);
        $imageModel = new Image([
          'file' => $fullPath,
          'url' => Util::replaceCDN($response['ObjectURL']),
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
            })->encode('jpg');
          } catch(Exception $e) {
            $this->log($user->id, 'Create submission ' . $submission->id . ' - thumbnailing failed for image ' . $imageModel->id);
            return response()->json(['error' => 'Failed generating thumbnail, submission failed.'], Response::HTTP_INTERNAL_SERVER_ERROR);
          }

          $thumbnailPath = env('SPACES_FOLDER') . '/' . $user->id . '/thumbnails/' . preg_replace('/.(gif|jpeg|png)/', '.jpg', $imgName);

          $thumbResponse = $space->UploadFile($interventionImage->stream(), 'public', $thumbnailPath, $interventionImage->mime());
          // TODO Handle failed upload.
            
          $this->log($user->id, 'Create submission ' . $submission->id . ' - save thumbnail ' . $thumbnailPath);

          $thumbSize = $interventionImage->getSize();
          $thumbnailModel = new Image([
            'file' => $thumbnailPath,
            'url' => Util::replaceCDN($thumbResponse['ObjectURL']),
            'hash' => Util::imageHash($interventionImage),
            'size' => $interventionImage->filesize(),
            'height' => $interventionImage->height(),
            'width' => $interventionImage->width(),
            'mime' => $interventionImage->mime(),
            'image_parent_id' => $imageModel->id,
            'image_parent_type' => Image::class,
          ]);

          if (!$thumbnailModel->save()) {
            $submission->images()->delete();
            $submission->delete();
            $this->log($user->id, 'Create submission ' . $submission->id . ' - failed saving thumbnail for image ' . $imageModel->id);
            return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
          }
        } else {
          $this->log($user->id, 'Create submission ' . $submission->id . ' - failed saving image');
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
          Util::logLine(config('constants.LOG.STREAK'), $user->id, 'Create streak ' . $streak->id . ' - success');
        } else {
          Util::logLine(config('constants.LOG.STREAK'), $user->id, 'Create streak - failed');
        }
      }

      $this->log($user->id, 'Create submission ' . $submission->id . ' - success');
      return response()->json($submission, Response::HTTP_OK);
    } else {
      $this->log($user->id, 'Create submission - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request) {
    // TODO
  }

  public function monthlyLeaderboard(Request $request) {
    $time = Carbon::now()->subMonths($request->input('offset', 0));
    $period = $time->year . '-'.  str_pad($time->month,  2, '0', STR_PAD_LEFT);

    $leaderboardQuery = Submission::query()
      ->select('users.id', 'users.username')
      ->selectRaw('SUM(hours) as total_hours, DATE_FORMAT(submissions.created_at, \'%Y-%m\') as time_period') // 2019-07
      ->having('time_period', '=', $period)
      ->join('users', 'users.id', 'submissions.user_id')
      ->groupBy('users.id', 'users.username', 'time_period');
    $personalQuery = clone $leaderboardQuery;

    $leaderboard = $leaderboardQuery
      ->orderBy('total_hours', 'desc')
      ->limit(10)
      ->get();

    $personal = null;
    if ($request->user) {
      $personal = $personalQuery
        ->where('users.id', $request->user->id)
        ->first();
    }

    return response()->json(['leaderboard' => $leaderboard, 'personal' => $personal], Response::HTTP_OK);
  }
}