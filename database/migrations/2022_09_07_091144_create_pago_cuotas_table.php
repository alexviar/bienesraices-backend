<?php

use App\Models\Cuota;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagoCuotasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pago_cuotas', function (Blueprint $table) {
            $table->id();
            $table->date("fecha");
            $table->char("moneda", 3);
            $table->decimal("importe", 19, 4);
            $table->foreignIdFor(Cuota::class);
            $table->foreign("moneda")->on("currencies")->references("code");
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
        Schema::dropIfExists('pago_cuotas');
    }
}
