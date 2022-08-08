<?php

namespace App\Models;

use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 
 * @property Carbon $vencimiento
 * @property Money $saldo_capital
 * @property Cuota $anterior
 * @property Cuota $siguiente
 * @method static Cuota find($id)
 */
class Cuota extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "numero",
        "vencimiento",
        "importe",
        "pago_extra",
        "saldo",
        "saldo_capital"
    ];

    protected $appends = [
        "multa",
        "total"
    ];

    protected $hidden = [
        "credito"
    ];

    protected $casts = [
        "vencimiento" => "date:Y-m-d"
    ];
    
    /** @var Carbon $fechaDeConsulta */
    public $fechaDeConsulta;
   
    /**
     * @param Carbon
     */
    function setFechaDeConsulta($fecha){
        $this->fechaDeConsulta = $fecha;
    }

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fechaDeConsulta = Carbon::today();
    }

    function getIsVencidaAttribute(){
        return $this->vencimiento->isBefore(Carbon::today());
    }

    function getIsPendienteAttribute(){
        return !$this->anterior || $this->anterior->isVencida;
    }

    function getImporteAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getPagoExtraAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getSaldoAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getTotalMultasAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getTotalPagosAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getDiasAttribute(){
        return $this->vencimiento->diffInDays($this->anterior ? $this->anterior->vencimiento : $this->credito->fecha);
    }

    function getInteresAttribute(){
        return $this->importe->plus($this->pago_extra)->minus($this->amortizacion);
        // $saldoAnterior = $this->anterior ?
        //     $this->anterior->saldo_capital :
        //     $this->credito->importe->minus($this->credito->cuota_inicial);
        // return $saldoAnterior->multipliedBy($this->fas->minus("1")->toScale(20, RoundingMode::HALF_UP))->round();
    }

    function getAmortizacionAttribute(){
        $saldoAnterior = $this->anterior ?
            $this->anterior->saldo_capital :
            $this->credito->importe->minus($this->credito->cuota_inicial);
        $amortizacion = $saldoAnterior->minus($this->saldo_capital);
        return $amortizacion;
    }

    function getMultaAttribute(){
        // $amount = $this->total->minus($this->attributes["saldo"]);
        return $this->total->minus($this->saldo);
    }

    function getTotalAttribute(){
        return new Money($this->calcularPago($this->fechaDeConsulta)->toScale(2, RoundingMode::HALF_UP), $this->getCurrency());
    }

    function getSaldoCapitalAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getFasAttribute(){
        return BigRational::of($this->credito->tasa_interes)->multipliedBy($this->dias)->dividedBy("360")->plus("1");
    }

    function computeFrc(){
        if($this->siguiente){
            $frc = $this->siguiente->computeFrc();
            return [
                $frc[0]->multipliedBy($this->fas),
                $frc[1]->plus($frc[0]),
            ];
        }
        return [
            BigRational::of($this->fas),
            BigRational::one()
        ];
    }

    function getFrcAttribute(){
        [$numerador, $denominador] = $this->computeFrc();
        return $numerador->dividedBy($denominador);
    }

    // function pagosExtras(){
    //     return $this->hasMany(PagoExtra::class, "numero", "periodo")->where("credito_id", $this->credito_id);
    // }

    function getPagosExtrasAttribute(){
        return $this->credito->pagosExtras->where("periodo", $this->numero)->sortBy(function($pe){
            return $pe->id;
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    function transacciones(){
        return $this->morphToMany(DetalleTransaccion::class, "transactable");
    }

    function credito(){
        return $this->belongsTo(Credito::class);
    }

    function getAnteriorCuotaAttribute(){
        return $this->credito->cuotas->where("numero", $this->numero - 1)->first();
    }    

    function getAnteriorAttribute(){
        return $this->credito->cuotas->where("numero", $this->numero - 1)->first();
    }  

    function getSiguienteAttribute(){
        return $this->credito->cuotas->where("numero", $this->numero + 1)->first();
    }

    function getCurrency(){
        return $this->credito->getCurrency();
    }

    function getReferenciaAttribute(){
        return $this->getReferencia();
    }

    function getReferencia(){
        return "Pago de la cuota {$this->numero} del crédito {$this->credito->id}";
    }

    /**
     * @param string|BigDecimal $pago
     * @param Carbon $fechaPago
     */
    function recalcular($pago, $fechaPago){
        $saldo = $this->attributes["saldo"];
        $nuevoSaldo = $this->calcularSaldo($saldo, $pago, $this->vencimiento, $fechaPago)->toScale(2, RoundingMode::HALF_UP);
        $multa = $nuevoSaldo->plus($pago)->minus($saldo);
        $this->saldo = $nuevoSaldo;
        $this->total_multas = $multa->plus($this->attributes["total_multas"]);
        $this->total_pagos = BigDecimal::of($pago)->plus($this->attributes["total_pagos"]);
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
        $fas = BigDecimal::of($this->credito->tasa_mora)
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
        $fas = BigDecimal::of($this->credito->tasa_mora)
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
            "moneda" => $this->getCurrency()->code,

            "saldo" => $this->saldo->amount,
            "multa" => (string) $pago->minus($this->saldo->amount)->toScale(2, RoundingMode::HALF_UP)
        ];
    }
}
