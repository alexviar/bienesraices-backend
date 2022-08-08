<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCuotasTableAddPagoExtraColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("cuotas", function(Blueprint $table){
            $table->decimal("pago_extra", 10)->default("0.00");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("cuotas", function(Blueprint $table){
            $table->dropColumn("pago_extra");
        });
    }
}
