<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToNetworkCampaignExcludeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('NetworkCampaignExclude', function(Blueprint $table)
		{
			$table->foreign('campaign_id', 'FK_E0FF72B3F639F774')->references('id')->on('NetworkCampaign')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('NetworkCampaignExclude', function(Blueprint $table)
		{
			$table->dropForeign('FK_E0FF72B3F639F774');
		});
	}

}
