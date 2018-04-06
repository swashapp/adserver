<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWebsiteExcludeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('WebsiteExclude', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('website_id')->index('IDX_BEDA59518F45C82');
			$table->binary('name', 64);
			$table->binary('min', 64);
			$table->binary('max', 64);
			$table->index(['website_id','name','min'], 'min');
			$table->index(['website_id','name','max'], 'max');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('WebsiteExclude');
	}

}
