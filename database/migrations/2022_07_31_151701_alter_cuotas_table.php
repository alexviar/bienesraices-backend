<?php

use App\Models\Cuota;
use App\Models\DetalleTransaccion;
use App\Models\Credito;
use App\Models\Venta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterCuotasTable extends Migration
{

    private function migrateData()
    {
        $select = Venta::select([
            // "importe",
            "cuota_inicial",
            "tasa_interes",
            "tasa_mora",
            "plazo",
            "periodo_pago",
            "dia_pago",
            "id",
        ])->selectRaw("? as creditable_type", [Venta::class])->where("tipo", 2);
        
        DB::table("creditos")->insertUsing([
            // "importe",
            "cuota_inicial",
            "tasa_interes",
            "tasa_mora",
            "plazo",
            "periodo_pago",
            "dia_pago",
            "creditable_id",
            "creditable_type",
        ], $select);
    
        Cuota::join("creditos", "creditable_id", "venta_id")->update([
            "credito_id" => DB::raw("`creditos`.`id`")
        ]);

        DB::table("cuotas")->joinSub(
            DB::table("detalles_transaccion")->select([
                "transactable_id",
            ])->selectRaw("SUM(importe) as total")->where("transactable_type", Cuota::class)
            ->groupBy("transactable_id"),
            "sub",
            "sub.transactable_id",
            "cuotas.id"
        )->update([
            "total_multas" => DB::raw("sub.total - (cuotas.importe - cuotas.saldo)"),
            "total_pagos" => DB::raw("sub.total")
        ]);
    
        DB::table("transactables")->insertUsing([
            "detalle_transaccion_id",
            "transactable_id",
            "transactable_type"
        ], DetalleTransaccion::leftJoin("creditos", function($join){
            $join->on("creditos.creditable_id", "transactable_id")
                ->where("transactable_type", Venta::class);
        })->select(
            "detalles_transaccion.id"
        )->selectRaw("(CASE WHEN creditos.creditable_id IS NULL THEN transactable_id ELSE creditos.id END)")
        ->selectRaw("(CASE WHEN creditos.creditable_id IS NULL THEN transactable_type ELSE ? END)", [Credito::class]));
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
        DB::statement("SET FOREIGN_KEY_CHECKS = 0");
        Schema::create('creditos', function (Blueprint $table) {
            $table->id();
            // $table->decimal("importe", 10);
            $table->decimal("cuota_inicial", 10);
            $table->decimal("tasa_interes", 4, 4);
            $table->decimal("tasa_mora", 4, 4);
            $table->tinyInteger("plazo");
            $table->tinyInteger("periodo_pago");
            $table->tinyInteger("dia_pago");

            // $table->foreignIdFor(Venta::class)->constrained();
            $table->morphs("creditable");
            $table->timestamps();
        });
        Schema::table('cuotas', function (Blueprint $table) {
            $table->decimal("total_multas",10)->default("0");
            $table->decimal("total_pagos",10)->default("0");
            $table->foreignIdFor(Credito::class)->constrained();
        });
        Schema::create('transactables', function(Blueprint $table){
            $table->foreignIdFor(DetalleTransaccion::class, "detalle_transaccion_id")->constrained("detalles_transaccion");
            $table->morphs("transactable");
        });

        $this->migrateData();

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn("cuota_inicial");
            $table->dropColumn("tasa_interes");
            $table->dropColumn("tasa_mora");
            $table->dropColumn("plazo");
            $table->dropColumn("periodo_pago");
            $table->dropColumn("dia_pago");
        });
        Schema::table('cuotas', function (Blueprint $table) {
            $table->dropColumn("venta_id");
        });
        Schema::table('detalles_transaccion', function (Blueprint $table) {
            $table->dropMorphs("transactable");
        });
        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
