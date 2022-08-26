<?php

use App\Models\Credito;
use App\Models\Cuota;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table("creditos", function(Blueprint $table){
            $table->decimal("importe_cuotas", 10)->nullable();
            $table->tinyInteger("estado")->default(1);
        });
        Credito::join("cuotas", function($query){
            $query->on("cuotas.credito_id", "creditos.id")
            ->where("cuotas.numero", 1);
        })->update(["importe_cuotas" => DB::raw("`cuotas`.`importe`")]);
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
        Schema::table("creditos", function(Blueprint $table){
            $table->dropColumn("importe_cuotas");
            $table->dropColumn("estado");
        });
    }
}
