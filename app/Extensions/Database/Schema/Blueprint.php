<?php

namespace App\Extensions\Database\Schema;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class Blueprint extends BaseBlueprint {
  /**
	 * Add a "deleted at" datetime for the table, that has the default constants.NOT_DELETED soft-delete value.
   *
   * @param  string  $column
   * @param  int  $precision
   * @return \Illuminate\Database\Schema\ColumnDefinition
   */
  public function softDeletes($column = 'deleted_at', $precision = 0) {
    return $this->datetime($column, $precision)->default(config('constants.NOT_DELETED'));
  }
}
