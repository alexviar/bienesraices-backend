<?php

use Doctrine\DBAL\Types\SmallIntType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnulacionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Type::hasType('tinyinteger')) {
            Type::addType('tinyinteger', SmallIntType::class);
        }
        Schema::create('anulaciones', function (Blueprint $table) {
            $table->id();
            $table->date("fecha");
            $table->string("motivo");
            $table->morphs("anulable");
            $table->timestamps();
        });

        Schema::table('transacciones', function (Blueprint $table) {
            $table->tinyInteger("estado")->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('anulaciones');
    }
}
