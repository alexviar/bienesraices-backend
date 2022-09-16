<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportePendienteColumnToVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->decimal("saldo", 19, 4)->after("importe");
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal("importe_pendiente", 19, 4)->after("importe");
            $table->decimal("saldo", 19, 4)->after("importe_pendiente");
        });

        Schema::table('creditos', function (Blueprint $table) {
            $table->dropColumn("cuota_inicial");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn("saldo", 19, 4);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn("importe_pendiente");
            $table->dropColumn("saldo");
        });

        Schema::table('creditos', function (Blueprint $table) {
            $table->decimal("cuota_inicial", 19, 4);
        });
    }
}
