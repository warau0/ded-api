<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateSubmissionsTable extends Migration {
  public function up() {
    $this->schema->create('submissions', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->text('description');
      $table->float('hours', 8, 2);
      $table->boolean('nsfw')->default(false);
      $table->boolean('private')->default(false);
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('user_id')->references('id')->on('users');
    });
  }

  public function down() {
    $this->schema->disableForeignKeyConstraints();
    $this->schema->dropIfExists('submissions');
    $this->schema->enableForeignKeyConstraints();
  }
}
