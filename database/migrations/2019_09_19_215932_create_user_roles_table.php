<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateUserRolesTable extends Migration {
  public function up() {
    $this->schema->create('user_roles', function (Blueprint $table) {
      $table->bigInteger('user_id')->unsigned();
      $table->bigInteger('role_id')->unsigned();
      $table->timestamps();
      $table->softDeletes();

      $table->unique(['user_id', 'role_id', 'deleted_at']);

      $table->foreign('user_id')->references('id')->on('users');
      $table->foreign('role_id')->references('id')->on('roles');
    });
  }

  public function down() {
    $this->schema->dropIfExists('user_roles');
  }
}
