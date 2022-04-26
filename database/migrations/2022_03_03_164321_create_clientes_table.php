<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger("tipo"); // Persona Natural, Persona Juridica
            $table->tinyInteger("tipo_documento");
            $table->string("numero_documento", 20);
            $table->string("apellido_paterno")->nullable();
            $table->string("apellido_materno")->nullable();
            $table->string("nombre");
            $table->string("telefono");
            $table->bigInteger("legacy_id")->nullable();
            $table->timestamps();

            $table->unique(["tipo_documento", "numero_documento"], "clientes_documento_identidad");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clientes');
    }
}
