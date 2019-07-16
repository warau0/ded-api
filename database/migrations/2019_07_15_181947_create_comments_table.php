<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateCommentsTable extends Migration {
  public function up() {
    $this->schema->create('comments', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->boolean('anonymous');
      $table->text('text');
      $table->bigInteger('comment_parent_id')->unsigned();
      $table->string('comment_parent_type');
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('user_id')->references('id')->on('users');
    });
  }

  public function down() {
    $this->schema->disableForeignKeyConstraints();
    $this->schema->dropIfExists('comments');
    $this->schema->enableForeignKeyConstraints();
  }
}
