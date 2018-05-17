<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNetworkCampaignRequiresTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('network_campaign_requires', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->timestamps();
            $table->timestamp('source_created_at')->nullable();
            $table->timestamp('source_updated_at')->nullable();

            $table->bigInteger('network_campaign_id')->unsigned();
            $table->binary('name', 64); // REQ CUSTOM ALTER
            $table->binary('min', 64); // REQ CUSTOM ALTER
            $table->binary('max', 64); // REQ CUSTOM ALTER
        });

        DB::statement("ALTER TABLE network_campaign_requires MODIFY name varbinary(64)");
        DB::statement("ALTER TABLE network_campaign_requires MODIFY min varbinary(64)");
        DB::statement("ALTER TABLE network_campaign_requires MODIFY max varbinary(64)");

        Schema::table('network_campaign_requires', function (Blueprint $table) {
            $table->index(['network_campaign_id','name','min'], 'min');
            $table->index(['network_campaign_id','name','max'], 'max');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('network_campaign_requires');
    }
}
