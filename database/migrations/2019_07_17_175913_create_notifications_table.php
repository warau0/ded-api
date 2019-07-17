<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration {
  public function up() {
    $this->schema->create('notifications', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->boolean('seen')->default(false);
      $table->text('text');
      $table->bigInteger('notification_parent_id')->unsigned()->nullable();
      $table->string('notification_parent_type')->nullable();
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down() {
    $this->schema->dropIfExists('notifications');
  }
}
