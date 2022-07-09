<?php

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Vendedor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->date("fecha");
            $table->tinyInteger("tipo");
            $table->char("moneda", 3);
            $table->decimal("importe", 10);
            $table->tinyInteger("estado")->default(1);
            
            $table->decimal("cuota_inicial", 10)->nullable();
            $table->decimal("tasa_interes", 4, 4)->nullable();
            $table->decimal("tasa_mora", 4, 4)->nullable();
            $table->tinyInteger("plazo")->nullable();
            $table->tinyInteger("periodo_pago")->nullable();
            $table->tinyInteger("dia_pago")->nullable();

            $table->bigInteger("legacy_id")->nullable();
            $table->foreign("moneda")->references("code")->on("currencies");
            $table->foreignIdFor(Proyecto::class);
            $table->foreignIdFor(Lote::class);
            $table->foreignIdFor(Cliente::class);
            $table->foreignIdFor(Vendedor::class)->nullable();
            $table->foreignIdFor(Reserva::class)->nullable();
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
        Schema::dropIfExists('ventas');
    }
}
