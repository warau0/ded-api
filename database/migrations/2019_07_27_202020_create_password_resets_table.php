<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class CreatePasswordResetsTable extends Migration {
  public function up() {
    $this->schema->create('password_resets', function (Blueprint $table) {
      $table->bigIncrements('id')->unsigned();
      $table->bigInteger('user_id')->unsigned();
      $table->string('token');
      $table->timestamps();
    });
  }

  public function down() {
    $this->schema->dropIfExists('password_resets');
  }
}
