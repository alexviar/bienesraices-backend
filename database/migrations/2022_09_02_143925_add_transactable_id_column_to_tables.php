<?php

use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Talonario;
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
        Schema::table('creditos', function (Blueprint $table) {
            $table->unsignedBigInteger("codigo");
        });

        Schema::table('cuotas', function (Blueprint $table) {
            $table->unsignedBigInteger("codigo");
        });

        $talonarioCredito = Talonario::where("tipo", Credito::class)->first();
        $siguiente = $talonarioCredito->siguiente;

        foreach(Credito::get()->groupBy("creditable_id") as $creditable_id => $creditos){
            $creditos->each->update([
                "codigo" => $siguiente
            ]);
            $prefijoCredito = $siguiente * 1000;
            Cuota::whereIn("credito_id", $creditos->pluck("id"))
            ->update([
                "codigo" => DB::raw("numero + $prefijoCredito")
            ]);
            $siguiente++;
        }
        $talonarioCredito->update([
            "siguiente" => $siguiente
        ]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('creditos', function (Blueprint $table) {
            $table->dropColumn("codigo");
        });

        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropColumn("codigo");
        });
    }
}
