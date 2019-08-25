<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateImagesTable extends Migration {
  public function up() {
    $this->schema->create('images', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->string('file');
      $table->string('url');
      $table->string('hash');
      $table->integer('size')->default(0);
      $table->integer('height')->default(0);
      $table->integer('width')->default(0);
      $table->string('mime');
      $table->bigInteger('image_parent_id')->unsigned();
      $table->string('image_parent_type');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down() {
    $this->schema->dropIfExists('images');
  }
}
