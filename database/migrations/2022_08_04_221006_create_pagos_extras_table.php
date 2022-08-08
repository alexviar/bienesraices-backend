<?php

use App\Models\Credito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosExtrasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagos_extras', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger("tipo_ajuste"); //1: A prorrata, 2: A las ultimas cuotas, 3: Pago de intereses, 4: Pago de intereses acumulados
            $table->decimal('importe', 10);
            $table->integer('periodo');
            $table->foreignIdFor(Credito::class)->constrained();
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
        // Schema::dropIfExists('pagos_extras');
    }
}
