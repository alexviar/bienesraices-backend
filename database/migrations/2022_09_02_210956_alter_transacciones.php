<?php

use App\Models\Cliente;
use App\Models\Deposito;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 
 * Separa la relacion Transaccion <>-- DetalleTranssaccion en favor de Deposito <-- Transaccion
 * Motivacion:
 * Un cliente realiza N reservas, acude al banco a hacer sus pagos de cuota inicial o pago al contado
 * dependiendo la modalidad de las compras que desea realizar, las cuales no necesariamente seran iguales
 * para todas las compras. Suponiendo que realiza todos los pagos en un solo deposito, primero registremos
 * la primera venta adjuntaremos la informacion del deposito como informacion de pago (la cual debe ser unica).
 * A continuacion procedemos con el registro de la siguiente compra, pero no podremos adjuntar la informacion
 * del deposito porque ya fue incluida como parte de la primera venta.
 *
 */
class AlterTransacciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("SET FOREIGN_KEY_CHECKS = 0");
        Schema::create("depositos", function(Blueprint $table){
            $table->id();
            $table->date("fecha");
            $table->unsignedBigInteger("numero_transaccion")->unique();
            $table->char("moneda",3);
            $table->decimal("importe", 19, 4);
            $table->decimal("saldo", 19, 4);
            $table->string("comprobante");
            $table->foreignIdFor(Cliente::class)->constrained();
            $table->foreign("moneda")->on("currencies")->references("code");
            $table->timestamps();
        });

        Schema::table("transacciones", function(Blueprint $table){
            $table->rename("transacciones_old");
        });

        Schema::table("detalles_transaccion", function(Blueprint $table){
            $table->dropConstrainedForeignId("transaccion_id");
            $table->date("fecha");
            $table->unsignedTinyInteger("metodo_pago");
            $table->string("observaciones", 255)->default("");
            $table->morphs("transactable");
            $table->foreignIdFor(Cliente::class)->constrained();
            $table->foreignIdFor(Deposito::class)->nullable()->constrained();
            $table->rename("transacciones");
        });
        // /** @var \Illuminate\Database\Eloquent\Collection<mixed, Transaccion> $transacciones */
        // $transacciones = Transaccion::with(["detalles.reservas", "detalles.ventas", "detalles.creditos", "detalles.cuotas"])->get();
        // foreach($transacciones as $transaccion){
        //     $detalle = $transaccion->detalles->first();
        //     if($reserva = $detalle->reservas->first()){
        //         $transaccion->cliente_id = $reserva->cliente_id;
        //     }
        //     else if($venta = $detalle->ventas->first()){
        //         $transaccion->cliente_id = $venta->cliente_id;
        //     }
        //     else if($credito = $detalle->creditos->first()){
        //         $transaccion->cliente_id = $credito->creditable->cliente_id;
        //     }
        //     else if($cuota = $detalle->cuotas->first()){
        //         $transaccion->cliente_id = $cuota->credito->creditable->cliente_id;
        //     }
        //     else {
        //         // throw new Exception("Error");
        //     }
        //     $transaccion->update();
        // }
        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table("transacciones", function(Blueprint $table){
        //     $table->dropConstrainedForeignId("cliente_id");
        //     $table->dropColumn("ajuste_redondeo");
        //     $table->dropColumn("observaciones");
        // });
    }
}
