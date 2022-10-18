<?php

use App\Models\CategoriaLote;
use App\Models\Proyecto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCategoriaLotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categoria_lotes', function (Blueprint $table) {
            $table->id();
            $table->string("codigo", 4);
            $table->string("descripcion")->nullable();
            $table->decimal("precio_m2", 19, 4);
            $table->foreignIdFor(Proyecto::class)->constrained();
            $table->timestamps();

            $table->unique(["proyecto_id", "codigo"]);
        });

        DB::table("categoria_lotes")->insertUsing([
            "codigo",
            "precio_m2",
            "proyecto_id"
        ], Proyecto::selectRaw("'A' as codigo")
            ->addSelect(["precio_mt2", "id"])
        );

        Schema::table("proyectos", function(Blueprint $table) {
            $table->decimal("redondeo", 19, 4)->change();
            $table->dropColumn("precio_mt2");
        });

        DB::statement("SET FOREIGN_KEY_CHECKS = 0");
        Schema::table('lotes', function (Blueprint $table) {
            $table->foreignIdFor(CategoriaLote::class, "categoria_id")->constrained("categoria_lotes");
        });

        DB::table('lotes')->join("manzanas", "lotes.manzana_id", "manzanas.id")
            ->join("categoria_lotes", "manzanas.proyecto_id", "categoria_lotes.proyecto_id")
            ->update([
                "categoria_id" => DB::raw("categoria_lotes.id")
            ]);
        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categoria_lotes');
        Schema::table('lotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId("categoria_id");
        });
    }
}
