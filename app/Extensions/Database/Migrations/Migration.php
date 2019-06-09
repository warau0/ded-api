<?php

namespace App\Extensions\Database\Migrations;

use App\Extensions\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration as BaseMigration;
use DB;

class Migration extends BaseMigration
{
	protected $schema;

	public function __construct()
	{
		// To set the custom blueprint class containing the overridden softDeletes function.
		//
		$this->schema = DB::connection()->getSchemaBuilder();

		// replace blueprint
		$this->schema->blueprintResolver(function($table, $callback) {
			return new Blueprint($table, $callback);
		});
	}
}
