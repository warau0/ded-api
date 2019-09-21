<?php

namespace App\Http\Controllers;

use App\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Intervention\Image\ImageManager;
use Carbon\Carbon;

class RoleController extends Controller {
  private function log($userID, $msg) {
    Util::logLine(config('constants.LOG.ROLE'), $userID, $msg);
  }

  public function createRoles() {
    $roles = [
      ['title' => 'Admin', 'slug' => 'admin', 'visible' => true,
        'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
    ];

    try {
      $result = Role::insert($roles);
    } catch(\Exception $e) {}

    $allRoles = Role::all();

    return response()->json(['roles' => $allRoles], Response::HTTP_OK);
  }
}