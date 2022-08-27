<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *  Usar decimal(19,4) es una opcion muy usada para almacenar dinero
 */
class AlterMoneyColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("cuotas", function(Blueprint $table){
            $table->decimal("importe", 19, 4)->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
            $table->decimal("pago_extra", 19, 4)->change();//En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
            $table->decimal("saldo", 19, 4)->change();
            $table->decimal("saldo_capital", 19, 4)->change();
            $table->decimal("total_pagos", 19, 4)->change();//En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
            $table->dropColumn("total_multas");
        });

        Schema::table("ventas", function(Blueprint $table){
            $table->decimal("importe", 19, 4)->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("creditos", function(Blueprint $table){
            $table->decimal("cuota_inicial", 19, 4)->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
            $table->decimal("importe_cuotas", 19, 4)->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("detalles_transaccion", function(Blueprint $table){
            $table->decimal("importe", 19, 4)->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("exchange_rates", function(Blueprint $table){
            $table->decimal("buy_rate", 19, 5)->change();
            $table->decimal("sell_rate", 19, 5)->change();
        });

        Schema::table("lotes", function(Blueprint $table){
            $table->decimal("precio", 19, 4)->nullable()->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("pagos_extras", function(Blueprint $table){
            $table->decimal("importe", 19, 4)->nullable()->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("proyectos", function(Blueprint $table){
            $table->decimal("precio_mt2", 19, 4)->change();
            $table->decimal("precio_reservas", 19, 4)->change();
            $table->decimal("cuota_inicial", 19, 4)->change();
        });

        Schema::table("reservas", function(Blueprint $table){
            $table->decimal("importe", 19, 4)->nullable()->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("transacciones", function(Blueprint $table){
            $table->decimal("importe", 19, 4)->nullable()->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });

        Schema::table("ufv", function(Blueprint $table){
            $table->decimal("valor", 19, 5)->change(); //En la practica este campo solo debería almacenar dos decimales ¿Debería usar DECIMAL(19,2)? ¿O DECIMAL(17, 2)?
        });
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
