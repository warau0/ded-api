<?php

namespace App\Traits;

use App\Image;
use App\Facades\Util;
use Intervention\Image\Exception\NotReadableException;

trait SavesImages {
  public function saveImage($manager, $space, $image, $userID, $folder, $parentType, $parentID, $resize = null) {
    try {
      $interventionImage = $manager->make($image);
      $hash = Util::imageHash($interventionImage);
    } catch(NotReadableException $e) { // Single files that exceed upload_max_filesize will trigger this.
      return ['error' => 'Image too large (3 MB) or damaged.'];
    }

    if (!env('ALLOW_DUPLICATE_SUBMISSIONS')) {
      $existingImage = Image::where([
        ['size', '=', $image->getSize()],
        ['hash', '=', $hash],
        ['image_parent_type', '=', $parentType],
      ])->first();
      if ($existingImage) {
        return ['error' => 'Duplicate image detected, to avoid double posts, please alter your image to reupload.'];
      }
    }

    if (!Util::imageValidMime($interventionImage)) {
      return ['error' => 'Not a jpeg, png or gif image.'];
    }

    if ($resize) {
      try {
        // Transparent parts of gif turn blue, png turn black
        $interventionImage->fit($resize, $resize, function ($constraint) {
            $constraint->upsize();
        })->encode('jpg');
      } catch(\Exception $e) {
        return ['error' => 'Failed generating thumbnail.'];
      }
    }

    $imgName = Util::imageName($image->getClientOriginalName());
    $uploadStream = $image->path(); // Use UploadedFile path to tmp upload file because intervention stream breaks gifs.

    if ($resize) {      
      $imgName = preg_replace('/.(gif|jpeg|png)/', '.jpg', $imgName);
      $uploadStream = $interventionImage->stream();
    }

    $fullPath = env('SPACES_FOLDER') . '/' . $userID . '/' . $folder . '/' . $imgName;

    $response = $space->UploadFile($uploadStream, 'public', $fullPath, $interventionImage->mime());

    // TODO Handle failed upload.

    Util::logLine(config('constants.LOG.UPLOAD'), $userID, 'Upload image - ' . $fullPath);

    $imageModel = new Image([
      'file' => $fullPath,
      'url' => Util::replaceCDN($response['ObjectURL']),
      'hash' => $hash,
      'size' => $resize ? $interventionImage->filesize() : $image->getSize(),
      'height' => $interventionImage->height(),
      'width' => $interventionImage->width(),
      'mime' => $interventionImage->mime(),
      'image_parent_id' => $parentID,
      'image_parent_type' => $parentType,
    ]);

    if ($imageModel->save()) {
      return [
        'error' => null,
        'image' => $imageModel,
      ];
    } else {
      return ['error' => 'Failed saving image.'];
    }
  }
}
