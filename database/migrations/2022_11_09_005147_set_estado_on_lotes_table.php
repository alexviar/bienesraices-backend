<?php

use App\Models\Lote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetEstadoOnLotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Lote::whereHas("reserva")->update([
            "estado" => 3
        ]);
        Lote::whereHas("venta")->update([
            "estado" => 4
        ]);
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
