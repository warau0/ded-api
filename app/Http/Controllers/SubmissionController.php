<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Tag;
use App\Image;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Intervention\Image\Exception\NotWritableException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
class SubmissionController extends Controller {
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

  public function update(Request $request) {

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
          return response()->json(['images' => 'Duplicate image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!Util::imageValidMime($interventionImage)) {
          $submission->images()->delete();
          $submission->delete();
          return response()->json(['images' => 'Not a jpeg, png or gif image, submission failed.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $image->storeAs($path, $filename);
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
              return [
                  'error' => 'Failed generating thumbnail, submission failed.',
                  'error_code' => Response::HTTP_INTERNAL_SERVER_ERROR
              ];
          }

          $thumbnailName = $filename;
          $thumbnailFolder = storage_path('app/public/images/' . $user->id . '/thumbnails/');

          if (!File::isDirectory($thumbnailFolder)) {
              File::makeDirectory($thumbnailFolder, 0755, true);
          }

          try {
              $interventionImage->save($thumbnailFolder . $thumbnailName);
          } catch (NotWritableException $ex) {
              $submission->images()->delete();
              $submission->delete();
              return [
                  'error' => 'Could not save thumbnail to disk.',
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
            return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
          }
        } else {
          return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
      }

      return response()->json($submission, Response::HTTP_OK);
    } else {
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request) {

  }
}