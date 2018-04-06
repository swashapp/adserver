<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCampaignModifierTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('CampaignModifier', function(Blueprint $table)
		{
			$table->foreign('campaign_id', 'FK_9904BD87F639F774')->references('id')->on('Campaign')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('CampaignModifier', function(Blueprint $table)
		{
			$table->dropForeign('FK_9904BD87F639F774');
		});
	}

}
