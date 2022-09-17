<?php

namespace App\Http\Controllers;

use App\Events\TransaccionRegistrada;
use App\Models\Account;
use App\Models\Cuota;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Saldo;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CajaController extends Controller
{
    function index(Request $request)
    {
        $queryArgs = $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Transaccion::with("detalles"), $queryArgs);
    }

    function store(Request $request)
    {
        $today = Carbon::today();
        $request->mergeIfMissing([
            "fecha" => $today->format("Y-m-d")
        ]);

        //Mejor no usar la validacion exists para disminuir el numero de llamadas a la BD
        //¿Que tan conveniente es dejar que la solicitud falle?
        $payload = $request->validate([
            "fecha" => "date|before_or_equal:".$today->format("Y-m-d"),
            "cliente_id" => "required",
            "moneda" => "required",
            "comprobante" => "nullable|image",

            "detalles" => "required|array",
            "detalles.*.id" => "required_with:detalles",
            "detalles.*.type" => "required_with:detalles|in:".Reserva::class.",".Venta::class.",".Cuota::class,
            "detalles.*.importe" => "required_with:detalles|numeric",

            "medios_pago" => "required|array",
            "medios_pago.*.forma_pago" => "required_with:medios_pago|in:1,2",
            "medios_pago.*.importe" => "required_with:medios_pago|numeric"
        ]);

        $transaccion = DB::transaction(function() use($payload, $request){
            if($comprobante = Arr::get($payload, "comprobante")){
                $path = $comprobante->store("comprobantes");
                $payload["comprobante"] = $path;
            }
            $transaccion = Transaccion::create(Arr::except($payload, ["medios_pago", "detalles"]) + [
                "user_id" => $request->user()->id,
            ]);

            // $importeTotal = new Money("0", $payload["moneda"]);
            $detalles = collect($payload["detalles"])->sortBy(["id", "type"]);
            foreach($detalles as $key => $detalle) {
                $pagable = $detalle["type"]::find($detalle["id"]);
                
                $importe = new Money($detalle["importe"], $pagable->getCurrency()->code);
                $importe->round(2);
                $transaccion->importe = $transaccion->importe->plus($importe->exchangeTo($transaccion->moneda))->amount;

                $transaccion->detalles()->create(Arr::only($detalle, [
                    "forma_pago",
                ]) + [
                    "moneda" => $pagable->getCurrency()->code,
                    "importe" => $importe->amount,
                    "referencia" => $pagable->getReferencia(),
                    "pagable_id" => $pagable->getMorphKey(),
                    "pagable_type" => $pagable->getMorphClass()
                ]);

            }
            $transaccion->importe = $transaccion->importe->round(2)->amount;
            $transaccion->update();
    
            $pagoTotal = BigDecimal::zero();
            foreach($payload["medios_pago"] as $key => $pago){
                $pagoTotal = $pagoTotal->plus($pago["importe"])->toScale(2, RoundingMode::HALF_UP);
                $transaccion->detallesPago()->create(Arr::only($pago, ["forma_pago", "importe"]));
            }

            $saldo = $pagoTotal->minus($transaccion->importe->amount);
            // dd((string)$pagoTotal, (string)$transaccion->importe, (string)$saldo);
            if($saldo->isGreaterThan("0")){
                $account = Account::where("cliente_id", $payload["cliente_id"])
                    ->where("moneda", $payload["moneda"])
                    ->first();
                if(!$account){
                    $account = Account::create(Arr::only($payload, ["cliente_id", "moneda"]) + [
                        "balance" => "0"
                    ]);
                }
                do{
                    $account->refresh();
                    $updated = Account::where("id", $account->id)
                        ->where("updated_at", $account->updated_at)
                        ->update([
                            "balance" => (string) $account->balance->amount->plus($saldo)
                        ]);
                }while(!$updated);
            }
            else if($saldo->isLessThan("0")){
                throw new Exception("La suma de los pagos es inferior al importe a pagar");
            }
            TransaccionRegistrada::dispatch($transaccion);
            return $transaccion;
        });

        return $transaccion;
    }

    /**
     * @return Transaccion 
     */
    function findTransaccion($id)
    {
        $transaccion = Transaccion::find($id);
        if (!$transaccion) {
            throw new ModelNotFoundException("No existe una transacción con id '$id'");
        }
    }

    // function complete(Request $request, $id)
    // {
    //     $transaccion = $this->findTransaccion($id);
    //     $this->authorize("complete", [$transaccion]);

    //     $payload = $request->validate([
    //         // "moneda" => "required|exists:currencies,code",
    //         "observaciones" => "nullable|string|max:255",
    //         "ajuste_redondeo" => "nullable|numeric",

    //         "pagos" => "required|array",
    //         "pagos.*.metodo_pago" => "required|in:1,2,3",
    //         "pagos.*.moneda" => "required|exists:currencies,code",
    //         "pagos.*.importe" => "required|exists:currencies,code",
    //     ]);
        
    //     $totalAPagar = $transaccion->importe_total;
    //     $totalPagos = collect($payload["pagos"])->reduce(function(){
            
    //     }, new Money(0, $transaccion->moneda));
    //     if($totalAPagar->amount->isGreaterThan())

    //     DB::transaction(function() use($payload, $transaccion){
            
    //         Transaccion::where("id", $transaccion->id)
    //             ->where("updated_at", $transaccion->updated_at)
    //             ->update(Arr::except($payload, ["pagos"]));
            
    //         foreach ($payload["pagos"] as $key => ["metodo_pago" => $metodoPago, "moneda" => $moneda, "importe" => $importe]) {
    //             if ($metodoPago == 3) {
    //                 $query = Account::where("moneda", $moneda)
    //                     ->where("accountable_id", $transaccion->accountable_id)
    //                     ->where("accountable_type", $transaccion->accountable_type);
    //                 do{
    //                     $account = $query->first();
    //                     $nuevoBalance = $account->balance->minus($importe);
    //                     if ($nuevoBalance->isLessThan("0")) {
    //                         abort(response()->json([
    //                             "message" => "El saldo es insuficiente",
    //                             "payload" => [
    //                                 "account_id" => $account->id
    //                             ]
    //                         ], 309));
    //                     }
    //                     $updated = Account::where("id", $account->id)
    //                         ->where("updated_at", $account->updated_at)
    //                         ->update([
    //                             "balance" => (string) $nuevoBalance->amount
    //                         ]);
    //                 }while(!$updated);
    //             }
    //         }
    //     });
    // }
}
