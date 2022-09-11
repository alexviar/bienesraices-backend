<?php

namespace App\Models;

use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * 
 * @property Carbon $vencimiento
 * @property Money $importe
 * @property Money $interes
 * @property Money $pago_extra
 * @property Money $amortizacion
 * @property Money $saldo
 * @property Money $saldo_capital
 * @property Money $total
 * @property Money $multa
 * @property Money $total_multas  
 * @property Money $total_pagos
 * @property Cuota $anterior
 * @property Cuota $siguiente
 * @method static Cuota find($id)
 */
class Cuota extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "codigo",
        "numero",
        "vencimiento",
        "importe",
        "pago_extra",
        "saldo",
        "saldo_capital"
    ];

    protected $appends = [
        "dias",
        "interes",
        "amortizacion",
        "total",
        "multa",
        "total_multas",
        "vencida",
        "pendiente"
    ];

    protected $hidden = [
        "credito"
    ];

    protected $casts = [
        "vencimiento" => "date:Y-m-d"
    ];
    
    /** @var Carbon $projectionDate */
    protected $projectionDate;

    /** @var BigRational $_saldo */
    protected $_saldo;

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->projectionDate = Carbon::today();
    }
    
    function getMorphKeyName()
    {
        return "codigo";
    }

    function getVencidaAttribute(){
        return $this->vencimiento->isBefore($this->projectionDate);
    }

    function getPendienteAttribute(){
        return !$this->anterior || $this->anterior->projectTo($this->projectionDate)->vencida;
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

    function getTotalMultasAttribute(){
        return $this->total_pagos->minus($this->importe->plus($this->pago_extra)->minus($this->saldo));
    }

    function getTotalPagosAttribute($value){
        return new Money($value, $this->getCurrency());
    }

    function getDiasAttribute(){
        return $this->vencimiento->diffInDays($this->anterior ? $this->anterior->vencimiento : $this->credito->fecha);
    }

    function getInteresAttribute(){
        return $this->importe->plus($this->pago_extra)->minus($this->amortizacion);
    }

    function getAmortizacionAttribute(){
        $saldoAnterior = $this->anterior ?
            $this->anterior->saldo_capital :
            $this->credito->importe->minus($this->credito->cuota_inicial);
        $amortizacion = $saldoAnterior->minus($this->saldo_capital);
        return $amortizacion;
    }

    function getMultaAttribute(){
        return $this->total->minus($this->saldo);
    }

    function getTotalAttribute(){
        $total = $this->saldo_rational
            ->multipliedBy($this->getFactorActualizacion())
            ->toScale(2, RoundingMode::HALF_UP)->toScale(4);
        return new Money($total, $this->getCurrency());
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

    function getPagosExtrasAttribute(){
        return $this->credito->pagosExtras->where("periodo", $this->numero)->sortBy(function($pe){
            return $pe->id;
        });
    }

    #region Relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function pagos(){
        return $this->hasMany(PagoCuota::class);
    }

    function credito(){
        return $this->belongsTo(Credito::class);
    }
    #endregion
   
    /**
     * @param Carbon
     */
    function projectTo(Carbon $fecha){
        $this->projectionDate = $fecha;
        return $this;
    }
    // function getAnteriorCuotaAttribute(){
    //     return $this->credito->cuotas->where("numero", $this->numero - 1)->first();
    // }    

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
        return "Pago de la cuota {$this->numero} del crédito {$this->credito->codigo}";
    }

    function getFactorActualizacion(){
        if(!$this->projectionDate->isAfter($this->vencimiento)) return BigRational::one();
        /** @var UfvRepositoryInterface $ufvRepository */
        $ufvRepository = Container::getInstance()->make(UfvRepositoryInterface::class);
        $ufvVencimiento = $ufvRepository->findByDate($this->vencimiento);
        if(!$ufvVencimiento) throw new Exception("No se encontro el valor de la UFV en la fecha ".$this->vencimiento->format("Y-m-d"));
        $ufvPago = $ufvRepository->findByDate($this->projectionDate);
        if(!$ufvVencimiento) throw new Exception("No se encontro el valor de la UFV en la fecha ".$this->projectionDate->format("Y-m-d"));
        //Factor de mantenimiento de valor
        $fmv = $ufvPago->isLessThan($ufvVencimiento) ? BigRational::one() : BigRational::of($ufvPago)->dividedBy($ufvVencimiento);
        $fas = BigRational::of($this->credito->tasa_mora)
            ->multipliedBy($this->projectionDate->diffInDays($this->vencimiento))
            ->dividedBy("360")
            ->plus("1");
        return $fmv->multipliedBy($fas);
    }

    function recalcularSaldo(){
        $this->_saldo = null;
        $saldo = $this->saldo_rational;
        $this->saldo = $saldo->toScale(2, RoundingMode::HALF_UP)->toScale(4);
    }

    function getSaldoRationalAttribute(){
        if(!isset($this->_saldo)){
            $saldo = BigRational::of($this->importe->plus($this->pago_extra)->amount);
            $projectionDate = $this->projectionDate;
            foreach($this->pagos as $pago){
                $fechaPago = $pago->fecha;
                $this->projectTo($fechaPago);
    
                $importePago = BigRational::of($pago->getAttributeFromArray("importe"));
                $pagoProyectado = $importePago->dividedBy($this->getFactorActualizacion());
                $saldo = $saldo->minus($pagoProyectado);
            }
            $this->projectTo($projectionDate);
            $this->_saldo = $saldo;
        }
        return $this->_saldo;
    }
}
