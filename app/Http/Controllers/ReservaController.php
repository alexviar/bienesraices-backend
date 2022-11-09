<?php

namespace App\Http\Controllers;

use App\Events\ReservaCreated;
use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\Reserva;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservaController extends Controller
{
    function applyFilters($query, $queryArgs)
    {
    }

    function index(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        $this->authorize("viewAny", [Reserva::class, $proyecto, $request->all()]);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        $query = Reserva::with(["cliente", "vendedor", "lote.manzana"])->where("proyecto_id", $proyectoId)->latest();
        $user = $request->user();
        if ($user->vendedor_id) {
            $query->where("vendedor_id", $user->vendedor_id);
        }

        return $this->buildResponse($query, $queryArgs);
    }

    function store(Request $request, $proyectoId)
    {
        $proyecto = $this->findProyecto($proyectoId);
        $this->authorize("create", [Reserva::class, $proyecto, $request->all()]);
        $payload = $request->validate([
            "fecha" => "required|date",
            "lote_id" => ["required", function ($attribute, $value, $fail) use ($proyectoId) {
                $lote = Lote::find($value);
                if (!$lote || $lote->proyecto->id != $proyectoId) {
                    $fail('Lote inválido.');
                } else if ($lote->estado !== 1) {
                    $fail('El lote no esta disponible.');
                }
            }],
            "cliente_id" => "required|exists:clientes,id",
            "vendedor_id" => "required|exists:vendedores,id",
            "moneda" => "required|exists:currencies,code",
            "importe" => "required|numeric",
            "saldo_contado" => "required|numeric",
            "saldo_credito" => "required|numeric",
            "vencimiento" => "required|date"
        ]);

        $payload["importe"] = (string) BigDecimal::of($payload["importe"])->toScale(2, RoundingMode::HALF_UP);
        $payload["saldo_contado"] = (string) BigDecimal::of($payload["saldo_contado"])->toScale(2, RoundingMode::HALF_UP);
        $payload["saldo_credito"] = (string) BigDecimal::of($payload["saldo_credito"])->toScale(2, RoundingMode::HALF_UP);

        $reserva = DB::transaction(function () use ($payload, $proyectoId, $request) {
            $reserva = Reserva::create($payload + [
                "proyecto_id" => $proyectoId,
                "saldo" => $payload["importe"]
            ]);

            ReservaCreated::dispatch($reserva, $request->user()->id);

            return $reserva;
        });

        return $reserva;
    }

    private function findProyecto($proyectoId)
    {
        $proyecto = Proyecto::find($proyectoId);
        if (!$proyecto) {
            throw new ModelNotFoundException("El proyecto no existe");
        }
        return $proyecto;
    }
}
