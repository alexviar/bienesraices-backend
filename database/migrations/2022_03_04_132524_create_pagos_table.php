<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('pagos', function (Blueprint $table) {
        //     $table->id();
        //     $table->date("fecha");
        //     $table->tinyInteger("forma_pago"); //Efectivo o deposito/transferencia bancaria
        //     $table->decimal("importe");
        //     $table->string("comprobante")->nullable();
        //     $table->string("referecia")->nullable();
        //     $table->morphs("pagable");
        //     $table->timestamps();
        // });
        Schema::create('transacciones', function (Blueprint $table) {
            $table->id();
            $table->date("fecha");
            $table->tinyInteger("forma_pago"); //Efectivo o deposito/transferencia bancaria
            $table->char("moneda", 3);
            $table->decimal("importe");
            $table->bigInteger("numero_transaccion")->unsigned()->nullable()->unique(); //Solo requerido para depositios/transferencias
            $table->string("comprobante")->nullable(); //Solo requerido para depositos/transferencias
            $table->timestamps();

            $table->foreign("moneda")->references("code")->on("currencies");
        });

        Schema::create('detalles_transaccion', function (Blueprint $table) {
            $table->id();
            $table->char("moneda", 3);
            $table->decimal("importe");
            $table->string("referencia")->nullable();
            $table->morphs("transactable");
            $table->foreignId("transaccion_id")->constrained("transacciones");
            $table->timestamps();
            
            $table->foreign("moneda")->references("code")->on("currencies");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagos');
    }
}
