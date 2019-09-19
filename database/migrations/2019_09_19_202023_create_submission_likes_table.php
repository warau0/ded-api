<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateSubmissionLikesTable extends Migration {
  public function up() {
    $this->schema->create('submission_likes', function (Blueprint $table) {
      $table->bigInteger('user_id')->unsigned();
      $table->bigInteger('submission_id')->unsigned();
      $table->timestamps();
      $table->softDeletes();
      
      $table->unique(['user_id', 'submission_id', 'deleted_at']);

      $table->foreign('user_id')->references('id')->on('users');
      $table->foreign('submission_id')->references('id')->on('submissions');
    });
  }

  public function down() {
    $this->schema->dropIfExists('submission_likes');
  }
}
