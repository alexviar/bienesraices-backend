<?php

use App\Models\Credito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransactableIdColumnToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('reservas', function (Blueprint $table) {
        //     // $table->unsignedBigInteger("transactable_id")->virtualAs("`id`");
        //     $table->unsignedBigInteger("transactable_id");
        // });

        // Schema::table('ventas', function (Blueprint $table) {
        //     // $table->unsignedBigInteger("transactable_id")->virtualAs("`id`");
        //     $table->unsignedBigInteger("transactable_id");
        // });

        Schema::table('creditos', function (Blueprint $table) {
            // $table->date("fecha");
            $table->unsignedBigInteger("codigo");
            // $table->unsignedBigInteger("transactable_id")->virtualAs("`numero`");
            // $table->unsignedBigInteger("transactable_id");
        });

        Schema::table('cuotas', function (Blueprint $table) {
            // $table->unsignedBigInteger("transactable_id");
            $table->unsignedBigInteger("codigo");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn("transactable_id");
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->unsignedBigInteger("transactable_id");
        });

        Schema::table('creditos', function (Blueprint $table) {
            $table->dropColumn("numero");
            $table->dropColumn("transactable_id");
        });

        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropColumn("transactable_id");
        });
    }
}
