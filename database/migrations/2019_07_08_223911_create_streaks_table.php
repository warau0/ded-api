<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateStreaksTable extends Migration {
  public function up() {
    $this->schema->create('streaks', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->integer('frequency');
      $table->integer('count');
      $table->dateTime('start');
      $table->dateTime('end')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('user_id')->references('id')->on('users');
    });
  }

  public function down() {
    $this->schema->dropIfExists('streaks');
  }
}
