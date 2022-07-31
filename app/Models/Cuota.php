<?php

namespace App\Models;

use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $appends = [
        "multa",
        "total"
    ];

    protected $hidden = [
        "venta"
    ];

    protected $casts = [
        "vencimiento" => "date:Y-m-d"
    ];

    function getImporteAttribute($value){
        return new Money($value, Currency::find($this->venta->moneda));
    }

    function getSaldoAttribute($value){
        return new Money($value, Currency::find($this->venta->moneda));
    }

    function getMultaAttribute(){
        // $amount = $this->total->minus($this->attributes["saldo"]);
        return $this->total->minus($this->saldo);
    }

    function getTotalAttribute(){
        return new Money($this->calcularPago(Carbon::now())->toScale(2, RoundingMode::HALF_UP), $this->getCurrency());
    }

    function getSaldoCapitalAttribute($value){
        return new Money($value, Currency::find($this->venta->moneda));
    }

    function venta(){
        return $this->belongsTo(Venta::class);
    }

    function getAnteriorCuotaAttribute(){
        return $this->venta->cuotas->where("numero", $this->numero - 1)->first();
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
        $this->saldo = $this->calcularSaldo($this->saldo->amount, $pago, $this->vencimiento, $fechaPago);
    }

    function calcularSaldo($deuda, $pago, $fechaVencimiento, $fechaPago){

        if(!$fechaPago->isAfter($fechaVencimiento)) return BigDecimal::of($deuda)->minus($pago);

        $ufvVencimiento = UFV::firstWhere("fecha", $fechaVencimiento);
        $ufvPago = UFV::firstWhere("fecha", $fechaPago);
        if(!$ufvVencimiento || ! $ufvPago) $pagoActualizado = BigDecimal::of($pago);
        else $pagoActualizado = BigDecimal::of($pago)->multipliedBy(BigDecimal::of($ufvVencimiento->valor))->dividedBy(BigDecimal::of($ufvPago->valor), 20, RoundingMode::HALF_UP);

        // $fas = BigDecimal::one()->plus(
        //     BigDecimal::of($this->venta->tasaMora)->dividedBy(360, 20, RoundingMode::HALF_UP)
        // )->power($ufvPago->fecha->diffInDays($ufvVencimiento));
        $fas = BigDecimal::of($this->venta->tasa_mora)
        ->multipliedBy($fechaPago->diffInDays($fechaVencimiento))
        ->dividedBy(360, 20, RoundingMode::HALF_UP)
        ->plus(BigDecimal::one());

        return BigDecimal::of($deuda)->minus($pagoActualizado->dividedBy($fas, 20, RoundingMode::HALF_UP));
    }

    /**
     * @param Carbon $fechaPago
     */
    function calcularPago($fechaPago){
        $deuda = BigDecimal::of($this->attributes["saldo"]);
        $fechaVencimiento = $this->vencimiento;
        //Si la deuda es muy pequeña (menor a 1) entonces no se calcula la multa
        if(/* $deuda->isLessThan(BigDecimal::one())
           || */!$fechaPago->isAfter($fechaVencimiento)) return $deuda;

        /** @var UfvRepositoryInterface $ufvRepository */
        $ufvRepository = Container::getInstance()->make(UfvRepositoryInterface::class);
        $ufvVencimiento = $ufvRepository->findByDate($fechaVencimiento);
        $ufvPago = $ufvRepository->findByDate($fechaPago);
        if(!$ufvVencimiento || !$ufvPago) $deudaActualizada = $deuda;
        else $deudaActualizada = $deuda->multipliedBy(BigDecimal::of($ufvPago))->dividedBy(BigDecimal::of($ufvVencimiento), 20, RoundingMode::HALF_UP);
        // $fas = BigDecimal::one()->plus(
        //     BigDecimal::of($this->venta->tasaMora)->dividedBy(360, 20, RoundingMode::HALF_UP)
        // )->power($ufvPago->fecha->diffInDays($ufvVencimiento));
        // return $deudaActualizada->multipliedBy($factor);
        $fas = BigDecimal::of($this->venta->tasa_mora)
            ->multipliedBy($fechaVencimiento->diffInDays($fechaPago))
            ->dividedBy(360, 20, RoundingMode::HALF_UP)
            ->plus(BigDecimal::one())
            ;

        return $deudaActualizada->multipliedBy($fas);
    }

    function toTransactableArray($fecha){

        $pago = $this->calcularPago(
            $fecha
        );

        return [
            "id" => $this->id,
            "type" => self::class,
            "referencia" => $this->getReferencia(),
            "importe" => (string) $pago->toScale(2, RoundingMode::HALF_UP),
            "moneda" => $this->venta->moneda,

            "saldo" => $this->saldo->amount,
            "multa" => (string) $pago->minus($this->saldo->amount)->toScale(2, RoundingMode::HALF_UP)
        ];
    }
}
