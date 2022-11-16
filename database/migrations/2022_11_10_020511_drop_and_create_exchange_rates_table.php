<?php

use App\Models\ExchangeRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAndCreateExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {        
        Schema::dropIfExists('exchange_rates');
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date("start")->nullable();
            $table->date("end")->nullable();
            $table->char("source", 3);
            $table->char("target", 3);
            $table->decimal("rate", 19, 6);
            $table->boolean("indirect");

            $table->unique(["source", "target"]);
            $table->foreign("source")->references("code")->on("currencies");
            $table->foreign("target")->references("code")->on("currencies");
            $table->timestamps();
        });

        ExchangeRate::create([
            "source" => "USD",
            "target" => "BOB",
            "rate" => "6.96",
            "indirect" => false
        ]);
        ExchangeRate::create([
            "source" => "BOB",
            "target" => "USD",
            "rate" => "6.86",
            "indirect" => true
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists('exchange_rates');
    }
}
