<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingForeignKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('lotes', function (Blueprint $table) {
        //     $table->foreign(["manzana_id"])->references("id")->on("manzanas");
        // });

        Schema::table('manzanas', function (Blueprint $table) {
            $table->foreign(["plano_id"])->references("id")->on("planos");
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->foreign(["proyecto_id"])->references("id")->on("proyectos");
            $table->foreign(["lote_id"])->references("id")->on("lotes");
            $table->foreign(["cliente_id"])->references("id")->on("clientes");
            $table->foreign(["vendedor_id"])->references("id")->on("vendedores");
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreign(["proyecto_id"])->references("id")->on("proyectos");
            $table->foreign(["lote_id"])->references("id")->on("lotes");
            $table->foreign(["cliente_id"])->references("id")->on("clientes");
            $table->foreign(["vendedor_id"])->references("id")->on("vendedores");
            $table->foreign(["reserva_id"])->references("id")->on("reservas");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropForeign(["manzana_id"]);
        });

        Schema::table('manzanas', function (Blueprint $table) {
            $table->dropForeign(["plano_id"]);
        });

        Schema::table('reservas', function (Blueprint $table) {
            $table->dropForeign(["proyecto_id"]);
            $table->dropForeign(["lote_id"]);
            $table->dropForeign(["cliente_id"]);
            $table->dropForeign(["vendedor_id"]);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(["proyecto_id"]);
            $table->dropForeign(["lote_id"]);
            $table->dropForeign(["cliente_id"]);
            $table->dropForeign(["vendedor_id"]);
            $table->dropForeign(["reserva_id"]);
        });
    }
}
