<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCampaignTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Campaign', function(Blueprint $table)
		{
			$table->foreign('user_id', 'FK_E663708BA76ED395')->references('id')->on('User')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('Campaign', function(Blueprint $table)
		{
			$table->dropForeign('FK_E663708BA76ED395');
		});
	}

}
