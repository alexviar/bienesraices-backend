<?php

use App\Models\ExchangeRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExchangeRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->char("source", 3);
            $table->char("target", 3);
            $table->decimal("buy_rate", 10);
            $table->decimal("sell_rate", 10);

            $table->primary(["source", "target"]);
            $table->foreign("source")->references("code")->on("currencies");
            $table->foreign("target")->references("code")->on("currencies");
            $table->timestamps();
        });

        ExchangeRate::create([
            "source" => "USD",
            "target" => "BOB",
            "buy_rate" => "6.86",
            "sell_rate" => "6.96"
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
