<?php

use App\Models\Account;
use App\Models\Cliente;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Saldo;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSaldosAFavor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('saldos', function (Blueprint $table) {
            $table->decimal("importe", 19,4);
            $table->char("moneda", 3);
            $table->foreign("moneda")->on("currencies")->references("code");
            $table->foreignIdFor(Cliente::class);
            $table->timestamps();

            $table->unique(["cliente_id", "moneda"]);
        });

        $groupedTransacciones = Transaccion::get()->groupBy("cliente_id");
        foreach($groupedTransacciones as $idCliente => $transacciones){
            $saldos = [
                "BOB" => new Money("0", "BOB"),
                "USD" => new Money("0", "USD"),
            ];
            foreach($transacciones as $transaccion){
                $detalles = $transaccion->detalles;
                $saldo = $transaccion->importe;
                foreach($detalles as $detalle){
                    $saldo = $saldo->minus($detalle->importe->exchangeTo($saldo->moneda, [
                        "date" => $transaccion->fecha
                    ]));
                }
                $saldos[$transaccion->moneda] = $saldos[$transaccion->moneda]->plus($saldo);
            }
            Account::create([
                "cliente_id" => $idCliente,
                "importe" => (string) $saldos["BOB"]->amount->toScale(2, RoundingMode::HALF_UP),
                "moneda" => "BOB"
            ]);
            Account::create([
                "cliente_id" => $idCliente,
                "importe" => (string) $saldos["USD"]->amount->toScale(2, RoundingMode::HALF_UP),
                "moneda" => "USD"
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saldos');
    }
}
