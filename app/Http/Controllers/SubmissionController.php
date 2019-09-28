<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Tag;
use App\Image;
use App\Streak;
use App\SubmissionLike;
use App\UserFollow;
use App\User;
use App\Facades\Util;
use App\Traits\SavesImages;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\ImageManager;
use Carbon\Carbon;

class SubmissionController extends Controller {
  use SavesImages;

  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.SUBMISSION'), $userID, $msg);
  }

  public function index(Request $request) {
    $submissions = Submission::query()
      ->with('images.thumbnail')
      ->orderBy('id', 'desc')
      ->where('private', false)
      ->whereHas('images')
      ->simplePaginate(40);

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }

  public function followedIndex(Request $request) {
    $user = $request->user;

    $followedIDs = UserFollow::query()
      ->where('user_id', $user->id)
      ->pluck('follow_id');

    $submissions = Submission::query()
      ->with('images.thumbnail')
      ->orderBy('id', 'desc')
      ->where('private', false)
      ->whereHas('images')
      ->whereIn('user_id', $followedIDs)
      ->simplePaginate(20);

    return response()->json([
      'submissions' => $submissions,
      'followed' => $followedIDs,
    ], Response::HTTP_OK);
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

    $submissionLike = null;

    if ($user) {
      $submissionLike = SubmissionLike::query()->where([
        ['user_id', '=', $user->id],
        ['submission_id', '=', $submission->id],
      ])->first();
    }

    return response()->json([
      'submission' => $submission,
      'next_submission_id' => $nextSubmission ? $nextSubmission->id : null,
      'next_user_submission_id' => $nextUserSubmission ? $nextUserSubmission->id : null,
      'previous_submission_id' => $previousSubmission ? $previousSubmission->id : null,
      'previous_user_submission_id' => $previousUserSubmission ? $previousUserSubmission->id : null,
      'user_submissions' => $randomUserSubmissions,
      'like' => $submissionLike,
    ], Response::HTTP_OK);
  }

  public function update(Request $request, $id) {
    $this->validate($request, [
      'description' => 'sometimes|string|max:1000|nullable',
      'hours' => 'sometimes|numeric|nullable',
      'nsfw' => 'sometimes|numeric|nullable|in:1,0',
      'private' => 'sometimes|numeric|nullable|in:1,0',
      'tags.*.value' => 'required|string',
      'tags.*.id' => 'sometimes|integer|nullable',
    ]);

    $submission = Submission::find($id);
    $user = $request->user;

    if (!$submission) {
      $this->log($user->id, 'Edit submission ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    $submission->description = $request->input('description', '');
    $submission->hours = $request->input('hours', '0.0');
    $submission->nsfw = $request->input('nsfw', false);
    $submission->private = $request->input('private', false);

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

      $this->log($user->id, 'Edit submission ' . $submission->id . ' - success');
      return response()->json($submission, Response::HTTP_OK);
    } else {
      $this->log($user->id, 'Edit submission - failed');
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
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

    if (!$request->input('has_data', false)) { // Requests that exceed post_max_size will trigger this.
      $this->log($user->id, 'Create submission - max post size exceeded');
      return response()->json(['images' => 'Image too large (3 MB), submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

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
        $imageResult = $this->saveImage($manager, $space, $image, $user->id, 'images', Submission::class, $submission->id, null);

        if ($imageResult['error']) {
          $submission->images()->delete();
          $submission->delete();
          $this->log($user->id, 'Create submission - ' . $imageResult['error']);
          return response()->json(['images' => $imageResult['error']], Response::HTTP_UNPROCESSABLE_ENTITY);
        } else {
          $thumbnailResult = $this->saveImage($manager, $space, $image, $user->id, 'thumbnails', Image::class, $imageResult['image']->id, 300);
  
          if ($thumbnailResult['error']) {
            $submission->images()->delete();
            $submission->delete();
            $this->log($user->id, 'Create submission '. $submission->id . ' thumbnail for image ' . $imageResult['image']->id . ' - ' . $imageResult['error']);
            return response()->json(['images' => $imageResult['error']], Response::HTTP_UNPROCESSABLE_ENTITY);
          }
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

  public function likeSubmission(Request $request, $id) {
    $submission = Submission::find($id);

    $user = $request->user;

    if (!$submission) {
      $this->log($user ? $user->id : null, 'Like submission ' . $id . ' - not found');
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    if ($request->input('like')) {
      $existingLike = SubmissionLike::query()->where([
        ['user_id', '=', $user->id],
        ['submission_id', '=', $submission->id],
      ])->first();

      if ($existingLike) {
        return response()->json(['like' => $existingLike], Response::HTTP_OK); 
      }

      $like = new SubmissionLike([
        'user_id' => $user->id,
        'submission_id' => $submission->id,
      ]);

      if ($like->save()) {
        $this->log($user->id, 'Like submission ' . $submission->id . ' - success');
        return response()->json(['like' => $like], Response::HTTP_OK);
      } else {
        $this->log($user->id, 'Like submission ' . $submission->id . ' - failed');
        return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    } else {
      $result = SubmissionLike::query()
        ->where([
          'user_id' => $user->id,
          'submission_id' => $submission->id,
        ])
        ->delete();

      if ($result) {
        $this->log($user->id, 'Unlike submission ' . $submission->id . ' - success');
        return response()->json(['like' => null], Response::HTTP_OK);
      } else {
        $this->log($user->id, 'Unlike submission ' . $submission->id . ' - failed');
        return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }
  }

  public function taggedSubmissionIndex(Request $request, $id) {
    $user = User::query()
      ->where('username',  urldecode($id))
      ->orWhere('id', $id)
      ->first();

    if (!$user) {
      return response()->json(['error' => 'ID not found.'], Response::HTTP_NOT_FOUND);
    }

    $loggedInUser = $request->user;
    if ($loggedInUser && $user->id === $loggedInUser->id) {
      $where = [];
    } else {
      $where = [['private', '=', false]];
    }

    $submissions = Submission::query()
      ->with('images.thumbnail')
      ->join('submission_tags', function ($query) use ($request) {
        $query->on('submissions.id', '=', 'submission_tags.submission_id');
        $query->where('submission_tags.tag_id', '=', $request->input('tag_id', ''));
      })
      ->orderBy('submissions.created_at', 'desc')
      ->where($where)
      ->paginate(40);

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }
}