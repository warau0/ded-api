<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateUsersTable extends Migration {
  public function up() {
    $this->schema->create('users', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->string('username')->unique();
      $table->string('email')->nullable();
      $table->string('password');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down() {
    $this->schema->disableForeignKeyConstraints();
    $this->schema->dropIfExists('users');
    $this->schema->enableForeignKeyConstraints();
  }
}
