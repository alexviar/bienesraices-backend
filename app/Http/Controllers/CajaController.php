<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Cuota;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Saldo;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $payload = $request->validate([]);
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
