<?php

use App\Models\Plano;
use App\Models\Proyecto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePlanosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string("titulo", 100);
            $table->string("descripcion", 255)->nullable();
            $table->text("import_warnings")->nullable();
            $table->tinyInteger("estado")->default(1);
            $table->foreignIdFor(Proyecto::class)->constrained();
            $table->timestamps();
        });

        DB::table('planos')->insertUsing([
            "titulo",
            "proyecto_id"
        ], Proyecto::selectRaw("'Plano inicial' as titulo")
            ->addSelect("id")    
        );
        
        DB::statement("SET FOREIGN_KEY_CHECKS = 0");
        Schema::table('manzanas', function(Blueprint $table) {
            $table->foreignIdFor(Plano::class);
        });

        DB::table('manzanas')->join('planos', 'manzanas.proyecto_id', 'planos.proyecto_id')
            ->update([
                "plano_id" => DB::raw('planos.id')
            ]);
        DB::statement("SET FOREIGN_KEY_CHECKS = 1");

        Schema::table('manzanas', function(Blueprint $table) {
            $table->dropUnique(["proyecto_id", "numero"]);
            $table->dropColumn(["proyecto_id"]);
            $table->unique(["plano_id", "numero"]);
        });

        Schema::table('lotes', function(Blueprint $table) {
            $table->dropSpatialIndex(['geocerca']);
        });

        Schema::table('lotes', function(Blueprint $table) {
            $table->polygon('geocerca')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('planos');
    }
}
