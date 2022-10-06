<?php

use App\Models\Cuota;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePagoCuotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pago_cuotas', function (Blueprint $table) {
            $table->id();
            $table->date("fecha");
            $table->char("moneda", 3);
            $table->decimal("importe", 19, 4);
            $table->unsignedBigInteger("codigo_cuota");
            // $table->foreignIdFor(Cuota::class)->constrained();
            $table->foreign("moneda")->on("currencies")->references("code");
            $table->timestamps();
        });

        DB::table("pago_cuotas")->insertUsing([
            "fecha",
            "moneda",
            "importe",
            "codigo_cuota",
            // "cuota_id",
        ], DB::table("transacciones")
            ->join("detalles_transaccion", "transacciones.id", "detalles_transaccion.transaccion_id")
            ->join("transactables", "detalles_transaccion.id", "transactables.detalle_transaccion_id")
            ->join("cuotas", "transactables.transactable_id", "cuotas.id")
            ->where("transactables.transactable_type", Cuota::class)
            ->select([
                "transacciones.fecha",
                "detalles_transaccion.moneda",
                "detalles_transaccion.importe",
                "cuotas.codigo" 
                // "transactables.transactable_id"
            ])->distinct("detalles_transaccion.id")
        );
        
        Schema::drop("transactables");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pago_cuotas');
    }
}
