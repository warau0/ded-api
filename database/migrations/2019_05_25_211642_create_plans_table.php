<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration {
  public function up() {
    Schema::create('plans', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->integer('day')->unsigned();
      $table->integer('start')->unsigned();
      $table->integer('duration')->unsigned();
      $table->string('color');
      $table->text('text');
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('user_id')->references('id')->on('users');
    });
  }

  public function down() {
    Schema::dropIfExists('plans');
  }
}
