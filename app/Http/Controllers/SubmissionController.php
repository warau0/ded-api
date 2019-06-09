<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Tag;
use App\Image;
use App\Facades\Util;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\Exception\NotWritableException;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;

class SubmissionController extends Controller {
  public function index(Request $request) {
    $submissions = Submission::query()->with(['tags', 'images'])->get();
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
          $base64Image = clone $interventionImage;
          $hash = hash('sha256', $base64Image->encode('data-url'));
        } catch(NotReadableException $e) {
          $submission->delete();
          return response()->json(['images' => 'Invalid or damaged image file, failed saving.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $filename = time() . '_' . preg_replace('/[^a-z0-9]+/', '_', strtolower($image->getClientOriginalName()));
        $filename = Util::replaceLast('_', '.', $filename);
        $path = 'public/images/' . $user->id . '/original/';

        $existingImage = Image::where([
          ['size', '=', $image->getSize()],
          ['hash', '=', $hash]
        ])->first();
        if ($existingImage) {
          $submission->delete();
          return response()->json(['images' => 'Duplicate image, failed saving.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        switch($interventionImage->mime()) {
          case 'image/png': break;
          case 'image/jpeg': break;
          case 'image/gif': break;
          default: {
            $submission->delete();
            return response()->json(['images' => 'Not a jpeg, png or gif image, failed saving.'], Response::HTTP_UNPROCESSABLE_ENTITY);
          }
      }

        $image->storeAs($path, $filename);
        $imageModel = new Image([
            'name' => $filename,
            'path' => $path,
            'hash' => $hash,
            'size' => $image->getSize(),
            'height' => $interventionImage->height(),
            'width' => $interventionImage->width(),
            'extension' => $interventionImage->mime(),
            'image_parent_id' => $submission->id,
            'image_parent_type' => Submission::class,
        ]);
        $imageModel->save();
      }

      return response()->json($submission, Response::HTTP_OK);
    } else {
      return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function destroy(Request $request) {
    
  }
}