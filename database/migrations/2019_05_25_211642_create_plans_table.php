<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreatePlansTable extends Migration {
  public function up() {
    $this->schema->create('plans', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->integer('day')->unsigned();
      $table->integer('start')->unsigned();
      $table->integer('duration')->unsigned();
      $table->string('color')->default('sky');
      $table->text('text');
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('user_id')->references('id')->on('users');
    });
  }

  public function down() {
    $this->schema->dropIfExists('plans');
  }
}
