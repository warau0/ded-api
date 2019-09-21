<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateRolesTable extends Migration {
  public function up() {
    $this->schema->create('roles', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->string('title');
      $table->string('slug');
      $table->boolean('visible')->default(true);
      $table->timestamps();
      $table->softDeletes();

      $table->unique(['slug', 'deleted_at']);
    });
  }

  public function down() {
    $this->schema->dropIfExists('roles');
  }
}
