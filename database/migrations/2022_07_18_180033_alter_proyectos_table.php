<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterProyectosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->dropColumn("socio");
            $table->renameColumn("precio_reserva", "precio_reservas");
            $table->tinyInteger("duracion_reservas")->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('proyectos', function (Blueprint $table) {
            $table->string("socio", 100);
            $table->renameColumn("precio_reservas", "precio_reserva");
            $table->dropColumn("duracion_reservas")->unsigned();
        });
    }
}
