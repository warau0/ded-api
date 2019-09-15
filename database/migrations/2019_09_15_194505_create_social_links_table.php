<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateSocialLinksTable extends Migration {
  public function up() {
    $this->schema->create('social_links', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->string('link');
      $table->timestamps();
      $table->softDeletes();
      
      $table->foreign('user_id')->references('id')->on('users');
    });
  }

  public function down() {
    $this->schema->dropIfExists('social_links');
  }
}
