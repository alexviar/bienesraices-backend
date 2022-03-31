<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Cuota extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "numero",
        "vencimiento",
        "importe",
        "saldo",
        "saldo_capital"
    ];

    function venta(){
        return $this->belongsTo(Venta::class);
    }

    function getCurrency(){
        return $this->venta->currency;
    }

    function getReferencia(){
        return "Pago de la cuota {$this->numero} del crédito {$this->venta->id}";
    }

    /**
     * @param string|BigDecimal $pago
     * @param Carbon $fechaPago
     */
    function recalcularSaldo($pago, $fechaPago){
        $this->saldo = $this->calcularSaldo($this->saldo, $pago, $this->vencimiento, $fechaPago);
    }

    function calcularSaldo($deuda, $pago, $fechaVencimiento, $fechaPago){

        if(!$fechaPago->isAfter($fechaVencimiento)) return BigDecimal::of($deuda)->minus($pago);

        $ufvVencimiento = UFV::firstWhere("fecha", $fechaVencimiento);
        $ufvPago = UFV::firstWhere("fecha", $fechaPago);
        if(!$ufvVencimiento || ! $ufvPago) $pagoActualizado = BigDecimal::of($pago);
        else $pagoActualizado = BigDecimal::of($pago)->multipliedBy(BigDecimal::of($ufvVencimiento->valor))->dividedBy(BigDecimal::of($ufvPago->valor), 10, RoundingMode::HALF_UP);

        // $fas = BigDecimal::one()->plus(
        //     BigDecimal::of($this->venta->tasaMora)->dividedBy(360, 10, RoundingMode::HALF_UP)
        // )->power($ufvPago->fecha->diffInDays($ufvVencimiento));
        $fas = BigDecimal::of($this->venta->tasa_mora)
        ->multipliedBy($fechaPago->diffInDays($fechaVencimiento))
        ->dividedBy(360, 10, RoundingMode::HALF_UP)
        ->plus(BigDecimal::one());

        return BigDecimal::of($deuda)->minus($pagoActualizado->dividedBy($fas, 10, RoundingMode::HALF_UP));
    }

    /**
     * @param Carbon $fechaPago
     * @param Carbon $fechaVencimiento
     */
    function calcularPago($deuda, $fechaVencimiento, $fechaPago){
        $deuda = BigDecimal::of($deuda);
        //Si la deuda es muy pequeña (menor a 1) entonces no se calcula la multa
        if(/* $deuda->isLessThan(BigDecimal::one())
           || */!$fechaPago->isAfter($fechaVencimiento)) return $deuda;

        $ufvVencimiento = UFV::firstWhere("fecha", $fechaVencimiento);
        $ufvPago = UFV::firstWhere("fecha", $fechaPago);
        if(!$ufvVencimiento || ! $ufvPago) $deudaActualizada = BigDecimal::of($deuda);
        else $deudaActualizada = BigDecimal::of($deuda)->multipliedBy(BigDecimal::of($ufvPago->valor))->dividedBy(BigDecimal::of($ufvVencimiento->valor), 10, RoundingMode::HALF_UP);
        // $fas = BigDecimal::one()->plus(
        //     BigDecimal::of($this->venta->tasaMora)->dividedBy(360, 10, RoundingMode::HALF_UP)
        // )->power($ufvPago->fecha->diffInDays($ufvVencimiento));
        // return $deudaActualizada->multipliedBy($factor);
        $fas = BigDecimal::of($this->venta->tasa_mora)
        ->multipliedBy($fechaVencimiento->diffInDays($fechaPago))
        ->dividedBy(360, 10, RoundingMode::HALF_UP)
        ->plus(BigDecimal::one());

        return $deudaActualizada->multipliedBy($fas);
    }

    function toTransactableArray($fecha){
        // $numero = $this->venta->cuotas->search(function($cuota){
        //     return $cuota->id === $this->id;
        // }) + 1;
        $numero = $this->numero;

        $pago = $this->calcularPago(
            $this->saldo,
            Carbon::createFromFormat("!Y-m-d", $this->vencimiento),
            $fecha
        );

        return [
            "id" => $this->id,
            "type" => self::class,
            "referencia" => "Pago de la cuota $numero del crédito {$this->venta->id}",
            "importe" => (string) $pago->toScale(2, RoundingMode::HALF_UP),
            "moneda" => $this->venta->moneda,

            "saldo" => $this->saldo,
            "multa" => (string) $pago->minus($this->saldo)->toScale(2, RoundingMode::HALF_UP)
        ];
    }
}
