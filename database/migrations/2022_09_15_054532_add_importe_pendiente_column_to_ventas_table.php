<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        DB::table("ventas")->join("creditos", "ventas.id", "creditos.creditable_id")
            ->update([
                "ventas.importe" => DB::raw("creditos.cuota_inicial"),
                "ventas.importe_pendiente" => DB::raw("ventas.importe - creditos.cuota_inicial")
            ]);

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
            $table->dropColumn("saldo");
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn("importe_pendiente");
            $table->dropColumn("saldo");
        });

        Schema::table('creditos', function (Blueprint $table) {
            $table->decimal("cuota_inicial");
        });
    }
}
