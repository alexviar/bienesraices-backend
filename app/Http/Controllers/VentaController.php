<?php

namespace App\Http\Controllers;

use App\Events\VentaCreated;
use App\Http\Reports\Venta\HistorialPagos;
use App\Http\Reports\Venta\PlanPagosPdfReporter;
use App\Models\Credito;
use App\Models\Currency;
use App\Models\DetalleTransaccion;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\Talonario;
use App\Models\Transaccion;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use NumberFormatter;
use Throwable;

class VentaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
 
    }

    function index(Request $request, $proyectoId)
    {
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $data = $this->buildResponse(Venta::with(["cliente", "vendedor", "lote.manzana", "credito"])->where("proyecto_id", $proyectoId)->latest(), $queryArgs);
        return $data;
    }

    function print_nota_venta(Request $request, $proyectoId, $ventaId)
    {
        $venta = $this->findVenta($proyectoId, $ventaId);
        $image = public_path("logo192.png");
        $mime = getimagesize($image)["mime"];
        $data = file_get_contents($image);
        $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);
        $importe = ($venta->tipo == 1 ? $venta->importe : $venta->credito->total_credito)->round(2, RoundingMode::HALF_UP);
        $numberFormatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
        $importeEntero = $importe->amount->toScale(0, RoundingMode::DOWN);
        if($request->html){
            return view("pdf.nota_venta", [
                "logo" => $dataUri,
                "fecha" => $venta->fecha->format("d/m/Y"),
                "numero" => $venta->id,
                "tipoVenta" => $venta->tipo == 1 ? "CONTADO" : "CRÉDITO",
                "importeNumeral" => (string) $importe,
                "importeTextual" => [
                    "{$numberFormatter->format((string)$importeEntero)} {$venta->currency->name}",
                    "{$numberFormatter->format((string)$importe->amount->minus($importeEntero)->multipliedBy("100"))}"
                ],
                "codigoLote" => "Mz {$venta->lote->manzana->numero}, Lt {$venta->lote->numero}",
                "nombreProyecto" => $venta->proyecto->nombre,
                "nombreCliente" => $venta->cliente->nombre_completo,
                "tipoDocumento" => $venta->cliente->documento_identidad["tipo_text"],
                "documento" => $venta->cliente->documento_identidad["numero"],
            // ])->setPaper([0, 0, 72*8.5, 72*13])->stream();
            ]);
        }
        return \Barryvdh\DomPDF\Facade\Pdf::loadView("pdf.nota_venta", [
            "logo" => $dataUri,
            "fecha" => $venta->fecha->format("d/m/Y"),
            "numero" => $venta->id,
            "tipoVenta" => $venta->tipo == 1 ? "CONTADO" : "CRÉDITO",
            "importeNumeral" => (string) $importe,
            "importeTextual" => [
                "{$numberFormatter->format((string)$importeEntero)} {$venta->currency->name}",
                "{$numberFormatter->format((string)$importe->amount->minus($importeEntero)->multipliedBy("100"))}"
            ],
            "codigoLote" => "Mz {$venta->lote->manzana->numero}, Lt {$venta->lote->numero}",
            "nombreProyecto" => $venta->proyecto->nombre,
            "nombreCliente" => $venta->cliente->nombre_completo,
            "tipoDocumento" => $venta->cliente->documento_identidad["tipo_text"],
            "documento" => $venta->cliente->documento_identidad["numero"],
        // ])->setPaper([0, 0, 72*8.5, 72*13])->stream();
        ])->setPaper("A6", "landscape")->stream();
    }

    /**
     * @return Venta
     */
    protected function findVenta($proyectoId, $ventaId){
        // $venta = Venta::where("proyecto_id", $proyectoId)->where("id", $ventaId)->first();
        $venta = Venta::find($ventaId);
        if(!$venta || $venta->proyecto_id != $proyectoId) throw new ModelNotFoundException();
        return $venta;
    }

    function preprocesStoreRequest(Request $request){
        $reserva = Reserva::find($request->get("reserva_id"));
        $monedaVenta = Currency::find($request->input("moneda"));
        return [$monedaVenta, $reserva];
    }

    function validateStoreRequest(Request $request, $proyectoId){        
        [$monedaVenta, $reserva] = $this->preprocesStoreRequest($request);
        $payload = $request->validate([
            "tipo" => "required|in:1,2",
            "fecha" => "required|date|before_or_equal:".now()->format("Y-m-d"),
            "moneda" => ["required", function($attribute, $value, $fail) use($monedaVenta){
                if(!$monedaVenta) $fail("Moneda invalida.");
            }],
            "lote_id" => ["required_without:reserva_id", function ($attribute, $value, $fail) use($request, $reserva, $proyectoId){
                $lote = $reserva ? $reserva->lote : Lote::find($value);
                if(!$lote || $lote->proyecto->id != $proyectoId){
                    $fail('Lote inválido.');
                }
                else if($lote->estado["code"] !== 1){
                    $cliente_id = $reserva ? $reserva->cliente_id : $request->input("cliente_id");
                    if($lote->estado["code"] === 3){
                        if($lote->reserva->cliente_id != $cliente_id){
                            $fail("El lote ha sido reservado por otro cliente.");
                        }
                    }
                    else{
                        $fail('El lote no esta disponible.');
                    }
                }
            }],
            "importe" => "required|numeric",
            "importe_pendiente" => "prohibited_unless:tipo,2|numeric",
            "cliente_id" => "required_without:reserva_id|nullable|exists:clientes,id",
            "vendedor_id" => "required_without:reserva_id|nullable||exists:vendedores,id",
            "reserva_id" => ["nullable", function ($attribute, $value, $fail) use($reserva){
                if(!$reserva){
                    $fail("Reserva invalida.");
                }
                else if($reserva->estado !== 1){
                    $fail("La reserva ha sido anulada o se ha concretado la venta.");
                }
                //No se realiza una restriccion basada en el vencimiento de la reserva
                //para dejar al criterio del operador si efectua o no la venta en casos excepcionales
            }],

            "credito" => "required_if:tipo,2",
            // "credito.cuota_inicial" => "required_with:credito|numeric",
            "credito.tasa_interes" => "required_with:credito|numeric",
            "credito.plazo" => "required_with:credito|numeric|integer",
            "credito.periodo_pago" => "required_with:credito|in:1,2,3,4,6",
            "credito.dia_pago" => "required_with:credito|min:1|max:31"
        ], [
            "fecha.before_or_equal" => "El campo ':attribute' no puede ser posterior a la fecha actual."
        ]);

        $payload["importe"] = (string) BigDecimal::of($payload["importe"])->toScale(2, RoundingMode::HALF_UP);
        if($pendiente = Arr::get($payload, "importe_pendiente")) $payload["importe_pendiente"] = (string) BigDecimal::of($pendiente)->toScale(2, RoundingMode::HALF_UP);

        return [$payload, $reserva];
    }

    function store(Request $request, $proyectoId){  
        $proyecto = Proyecto::find($proyectoId);
        if(!$proyecto){
            throw new ModelNotFoundException("El proyecto no existe");
        }    
        [$payload, $reserva] = $this->validateStoreRequest($request, $proyectoId);
        if($reserva){
            // $importe = $payload["tipo"] == 2 ? $reserva->saldo_credito : $reserva->saldo_contado;
            // $payload["importe"] = (string) $importe->exchangeTo($payload["moneda"])->round(2)->amount;
            // $payload["importe_pendiente"] = (string) $reserva->saldo_contado->minus($importe)->exchangeTo($payload["moneda"])->round(2)->amount;
            $payload["lote_id"] = $reserva->lote->id;
            $payload["cliente_id"] = $reserva->cliente_id;
            $payload["vendedor_id"] = $reserva->vendedor_id;
        }
        $payload["saldo"] = $payload["importe"];
        $payload["proyecto_id"] = $proyecto->id;

        $this->authorize("create", [Venta::class, $payload]);

        $record = DB::transaction(function() use($proyecto, $reserva, $payload, $request){
            /** @var Venta $record */
            $record = Venta::create($payload);

            if($record->tipo == 2) {
                /** @var Credito $credito */

                do{
                    $talonario = Talonario::where("tipo", Credito::class)->latest("id")->first();
                    $siguiente = $talonario->siguiente;
                    $updated = Talonario::where("id", $talonario->id)
                        ->where("updated_at", $talonario->updated_at)
                        ->update([
                            "siguiente" => $siguiente + 1
                        ]);
                }while(!$updated);
                $credito = $record->credito()->create($payload["credito"]+[
                    // "fecha" => $payload["fecha"],
                    "codigo" => $siguiente,
                    "tasa_mora" => $proyecto->tasa_mora,
                ]);
                $credito->build();
            }

            VentaCreated::dispatch($record, $request->user()->id);

            //Actualizar el estado de la reserva, si existe
            if($reserva){
                $reserva->estado = 3;
                $reserva->save();
            }

            //Actualizar el estado del lote


            return $record;
        });

        $record->loadMissing(["cliente", "vendedor", "lote.manzana"]);
        return $record;
    }
}
