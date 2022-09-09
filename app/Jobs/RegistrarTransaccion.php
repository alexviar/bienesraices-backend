<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\Deposito;
use App\Models\DetalleTransaccion;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RegistrarTransaccion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public $payload
    ){ 
        if($uploadedComprobante = Arr::get($payload, "deposito.comprobante")){
            $comprobantePath = $uploadedComprobante->store("comprobantes");
            Arr::set($this->payload, "deposito.comprobante", $comprobantePath);
        }
    }

    protected function createTransaccion(){
        /** @var Transaccion $transaccion */
        $transaccion = Transaccion::create(Arr::only($this->payload, [
            "fecha",
            "cliente_id",
            "moneda",
            "importe",
            // "ajuste_redondeo",
            "metodo_pago",
            "observaciones",
            "transactable_id",
            "transactable_type",
            "deposito_id"
        ]));
        return $transaccion;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $payload = $this->payload;

        if($payload["metodo_pago"] == 2){
            $deposito = Deposito::where("numero_transaccion", Arr::get($payload, "deposito.numero_transaccion"))->first()
                ?? Deposito::create($payload["deposito"]+[
                    "fecha" => $payload["fecha"],
                    "saldo" => Arr::get($payload, "deposito.importe"),
                    "cliente_id" => $payload["cliente_id"]
                ]);
            $payload["deposito_id"] = $deposito->id;

            $importe = (new Money($payload["importe"], Currency::find($payload["moneda"])))->exchangeTo($deposito->currency)->round(2);
            if($deposito->saldo->amount->isLessThan($importe->amount)){
                throw abort(409, "El pago excede el saldo del deposito.");
            }
            else{
                dd((string)$deposito->saldo, $payload["importe"]);
            }
            
            $this->createTransaccion();

            $deposito->recalcularSaldo();
            $deposito->update();
        }
        else{
            $this->createTransaccion();
        }
    }
}
