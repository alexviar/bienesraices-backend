<?php

use App\Models\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->char("code", 3)->primary();
            $table->string("name", 100);
            $table->timestamps();
        });

        Currency::create([
            "code" => "USD",
            "name" => "Dolar"
        ]);
        Currency::create([
            "code" => "BOB",
            "name" => "Boliviano"
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('currencies');
    }
}
