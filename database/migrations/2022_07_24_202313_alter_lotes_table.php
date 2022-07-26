<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterLotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("lotes", function(Blueprint $table) {
            $table->polygon("geocerca");
        });
        DB::table("lotes")->update(["geocerca" => DB::raw("ST_GeomFromText('POLYGON((0 0,0 0,0 0))')")]);
        Schema::table("lotes", function(Blueprint $table) {
            $table->spatialIndex("geocerca");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("lotes", function(Blueprint $table) {
            $table->dropSpatialIndex(["geocerca"])->spatialIndex();
            $table->dropColumn(["geocerca"]);
        });
    }
}
