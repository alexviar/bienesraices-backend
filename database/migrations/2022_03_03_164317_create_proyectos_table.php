<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProyectosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string("nombre", 100)->fulltext();
            $table->string("socio", 100);
            $table->point("ubicacion");

            $table->char("moneda", 3);
            $table->integer("redondeo");
            $table->decimal("precio_mt2", 10);
            $table->decimal("precio_reserva", 10);
            $table->decimal("cuota_inicial", 10);
            $table->decimal("tasa_interes", 4, 4);
            $table->decimal("tasa_mora", 4, 4);
            $table->bigInteger("legacy_id")->nullable();
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
        Schema::dropIfExists('proyectos');
    }
}
