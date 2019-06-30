<?php

namespace App\Http\Controllers;

use App\Submission;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller {
  public function submissions(Request $request, $id) {
    $submissions = Submission::query()
      ->with(['tags', 'images.thumbnail'])
      ->limit(48)
      ->orderBy('id', 'desc')
      ->where([
        ['private', '=', false],
        ['user_id', '=', $id]
      ])
      ->whereHas('images')
      ->get();

    return response()->json(['submissions' => $submissions], Response::HTTP_OK);
  }
}