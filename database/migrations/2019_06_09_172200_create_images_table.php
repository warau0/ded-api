<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateImagesTable extends Migration {
  public function up() {
    $this->schema->create('images', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->string('path');
      $table->string('name');
      $table->string('hash');
      $table->integer('size');
      $table->integer('height');
      $table->integer('width');
      $table->string('extension');
      $table->integer('imageable_id');
      $table->string('imagable_type');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down() {
    $this->schema->dropIfExists('images');
  }
}
