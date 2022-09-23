<?php

use App\Models\Transaccion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDetallePagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalles_pagos', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger("forma_pago");
            $table->char("moneda", 3);
            $table->decimal("importe", 19,4);
            $table->string("comprobante")->nullable();
            $table->unsignedBigInteger("numero_comprobante")->nullable();
            $table->foreign("moneda")->on("currencies")->references("code");
            $table->foreignIdFor(Transaccion::class)->constrained("transacciones");
            $table->timestamps();
        });

        DB::table("detalles_pagos")->insertUsing([
            "transaccion_id",
            "forma_pago",
            "moneda",
            "importe",
            "comprobante",
            "numero_comprobante"
        ], DB::table("transacciones")->select([
            "id",
            "forma_pago",
            "moneda",
            "importe",
            "comprobante",
            "numero_transaccion"
        ]));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detalles_pagos');
    }
}
