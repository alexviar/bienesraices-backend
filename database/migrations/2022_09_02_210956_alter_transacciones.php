<?php

use App\Models\Cliente;
use App\Models\Credito;
use App\Models\Cuota;
use App\Models\Deposito;
use App\Models\DetalleTransaccion;
use App\Models\Reserva;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\Venta;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
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
        if (!Type::hasType('char')) {
            Type::addType('char', StringType::class);
        }

        DB::statement("SET FOREIGN_KEY_CHECKS = 0");



        Schema::table("transacciones", function(Blueprint $table){
            $table->dropColumn("forma_pago");
            $table->dropColumn("comprobante");
            $table->dropColumn("numero_transaccion");
            $table->decimal("importe", 19, 4)->nullable()->change();
            $table->string("estado")->default(1);
            $table->foreignIdFor(Cliente::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
        });

        Schema::table("detalles_transaccion", function(Blueprint $table){
            $table->morphs("pagable");
        });

        DB::table("detalles_transaccion")
            ->join("transactables", "detalles_transaccion.id", "transactables.detalle_transaccion_id")
            ->whereIn("transactable_type", [Reserva::class, Venta::class])
            ->update([
                "detalles_transaccion.pagable_type" => DB::raw("transactables.transactable_type"),
                "detalles_transaccion.pagable_id" => DB::raw("transactables.transactable_id"),
            ]);

        DB::table("detalles_transaccion")
            ->join("transactables", "detalles_transaccion.id", "transactables.detalle_transaccion_id")
            ->join("creditos", "transactables.transactable_id", "creditos.id")
            ->whereIn("transactable_type", [Credito::class])
            ->update([
                "detalles_transaccion.pagable_type" => DB::raw("transactables.transactable_type"),
                "detalles_transaccion.pagable_id" => DB::raw("creditos.codigo"),
            ]);

        DB::table("detalles_transaccion")
            ->join("transactables", "detalles_transaccion.id", "transactables.detalle_transaccion_id")
            ->join("cuotas", "transactables.transactable_id", "cuotas.id")
            ->join("creditos", "cuotas.credito_id", "creditos.id")
            ->whereIn("transactable_type", [Cuota::class])
            ->update([
                "detalles_transaccion.pagable_type" => DB::raw("transactables.transactable_type"),
                "detalles_transaccion.pagable_id" => DB::raw("cuotas.codigo"),
            ]);

        //Actualizar importe de transacciones

        Transaccion::joinSub(DetalleTransaccion::join("transacciones", "detalles_transaccion.transaccion_id", "transacciones.id")->select([
            "transaccion_id",
            DB::raw(<<<SQL
                SUM(CASE
                    WHEN `transacciones`.`moneda` = 'BOB' THEN CASE
                            WHEN `detalles_transaccion`.`moneda` = 'BOB' THEN `detalles_transaccion`.`importe`
                            ELSE `detalles_transaccion`.`importe`*'6.96'
                        END
                    ELSE CASE
                            WHEN `detalles_transaccion`.`moneda` = 'USD' THEN `detalles_transaccion`.`importe`
                            ELSE `detalles_transaccion`.`importe`/'6.86'
                        END
                END) AS "importe"
            SQL)
        ])->groupBy("transaccion_id"), "sub", function($query){
            $query->on("sub.transaccion_id", "transacciones.id");
        })->update([
            "transacciones.importe" => DB::raw("ROUND(sub.importe, 2)")
        ]);

        $this->updateClienteIdForTransacciones();


        DB::statement("SET FOREIGN_KEY_CHECKS = 1");
    }

    function updateClienteIdForTransacciones(){
        DB::table("transacciones")->join("detalles_transaccion", function($query){
            $query->on("transacciones.id", "detalles_transaccion.transaccion_id");
        })->join("transactables", function($query){
            $query->on("detalles_transaccion.id", "transactables.detalle_transaccion_id")
                ->where("transactables.transactable_type", Reserva::class);
        })->join("reservas", function($query){
            $query->on("reservas.id", "transactables.transactable_id");
        })->update([
            "transacciones.cliente_id" => DB::raw("reservas.cliente_id")
        ]);
        
        DB::table("transacciones")->join("detalles_transaccion", function($query){
            $query->on("transacciones.id", "detalles_transaccion.transaccion_id");
        })->join("transactables", function($query){
            $query->on("detalles_transaccion.id", "transactables.detalle_transaccion_id")
                ->where("transactables.transactable_type", Venta::class);
        })->join("ventas", function($query){
            $query->on("ventas.id", "transactables.transactable_id");
        })->update([
            "transacciones.cliente_id" => DB::raw("ventas.cliente_id")
        ]);        
        
        DB::table("transacciones")->join("detalles_transaccion", function($query){
            $query->on("transacciones.id", "detalles_transaccion.transaccion_id");
        })->join("transactables", function($query){
            $query->on("detalles_transaccion.id", "transactables.detalle_transaccion_id")
                ->where("transactables.transactable_type", Credito::class);
        })->join("creditos", "transactables.transactable_id", "creditos.id")
        ->join("ventas", "creditos.creditable_id", "ventas.id")->update([
            "transacciones.cliente_id" => DB::raw("ventas.cliente_id")
        ]);
        
        DB::table("transacciones")->join("detalles_transaccion", function($query){
            $query->on("transacciones.id", "detalles_transaccion.transaccion_id");
        })->join("transactables", function($query){
            $query->on("detalles_transaccion.id", "transactables.detalle_transaccion_id")
                ->where("transactables.transactable_type", Cuota::class);
        })->join("cuotas", "transactables.transactable_id", "cuotas.id")
        ->join("creditos", "cuotas.credito_id", "creditos.id")
        ->join("ventas", "creditos.creditable_id", "ventas.id")->update([
            "transacciones.cliente_id" => DB::raw("ventas.cliente_id")
        ]);
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
