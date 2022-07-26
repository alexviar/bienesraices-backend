<?php

use App\Models\Manzana;
use App\Models\Proyecto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->string("numero");
            $table->decimal("superficie", 10, 2);
            $table->decimal("precio", 10, 2)->nullable();
            $table->tinyInteger("estado")->default(1);
            // $table->char("moneda", 3);

            // Podría mejorar el rendimiento agregar esta redundancia?
            // $table->foreignIdFor(Proyecto::class); 
            $table->foreignIdFor(Manzana::class)->constrained();
            $table->unique(["manzana_id", "numero"]);
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
        Schema::dropIfExists('lotes');
    }
}
