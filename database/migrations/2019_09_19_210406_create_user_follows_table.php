<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateUserFollowsTable extends Migration {
  public function up() {
    $this->schema->create('user_follows', function (Blueprint $table) {
      $table->bigInteger('user_id')->unsigned();
      $table->bigInteger('follow_id')->unsigned();
      $table->timestamps();
      $table->softDeletes();
      
      $table->unique(['user_id', 'follow_id', 'deleted_at']);

      $table->foreign('user_id')->references('id')->on('users');
      $table->foreign('follow_id')->references('id')->on('users');
    });
  }

  public function down() {
    $this->schema->dropIfExists('user_follows');
  }
}
