<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateSubmissionTagsTable extends Migration {
  public function up() {
    $this->schema->create('submission_tags', function (Blueprint $table) {
      $table->bigInteger('submission_id')->unsigned();
      $table->bigInteger('tag_id')->unsigned();

      $table->foreign('submission_id')->references('id')->on('submissions');
      $table->foreign('tag_id')->references('id')->on('tags');
    });
  }

  public function down() {
    $this->schema->dropIfExists('submission_tags');
  }
}
