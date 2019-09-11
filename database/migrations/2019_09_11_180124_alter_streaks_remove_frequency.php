<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Migrations\Migration;

class AlterStreaksRemoveFrequency extends Migration {
  public function up() {
    $this->schema->table('streaks', function (Blueprint $table) {
      $table->dropColumn('frequency');
    });
  }
}
