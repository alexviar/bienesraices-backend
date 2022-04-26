<?php

use App\Models\Cliente;
use App\Models\Proyecto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCodigoPagosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('codigos_pago', function (Blueprint $table) {
            $table->id();
            $table->string("codigo", 80)->unique();
            $table->foreignIdFor(Cliente::class);
            $table->foreignIdFor(Proyecto::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('codigo_pagos');
    }
}
