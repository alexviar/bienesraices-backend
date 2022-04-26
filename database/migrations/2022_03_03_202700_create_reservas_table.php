<?php

use App\Models\Cliente;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Vendedor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->date("fecha");
            $table->string("moneda");
            $table->decimal("importe");
            // $table->string("saldo_credito");
            // $table->string("saldo_contado");
            $table->date("vencimiento");
            $table->tinyInteger("estado")->default(1);
            $table->bigInteger("legacy_id")->nullable();
            $table->foreignIdFor(Proyecto::class);
            $table->foreignIdFor(Lote::class);
            $table->foreignIdFor(Cliente::class);
            $table->foreignIdFor(Vendedor::class)->nullable();
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
        Schema::dropIfExists('reservas');
    }
}
