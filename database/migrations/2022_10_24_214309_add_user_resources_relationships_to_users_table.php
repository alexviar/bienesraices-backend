<?php

use App\Models\Proyecto;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserResourcesRelationshipsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('user_resources', function (Blueprint $table) {
        //     $table->foreignIdFor(User::class)->constrained();
        //     $table->morph("resource");
        // });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignIdFor(Vendedor::class)->nullable()->constrained("vendedores");
        });

        Schema::create('proyecto_user', function (Blueprint $table) {
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Proyecto::class)->constrained();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table) {
            //
        });
    }
}
