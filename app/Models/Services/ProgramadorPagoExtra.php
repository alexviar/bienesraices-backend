<?php

namespace App\Models\Services;

use App\Models\Credito;
use App\Models\Cuota;
use App\Models\PagoExtra;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Log;

abstract class ProgramadorPagoExtra {

    /** @var ProgramadorPagoExtra|null $next */
    private $next;

    function __construct(
        $next=null
    ){
        $this->next = $next;
    }

    function cloneCuota(Cuota $cuota){
        $clone = $cuota->replicate();
        $clone->save();
        $clone->unsetRelation("pagos");
        $clone->pagos()->saveMany($cuota->pagos->map(function($pago){
            return $pago->replicate();
        }));
        return $clone;
    }

    function cloneCredito(Credito $credito){
        $clone = $credito->replicate(["estado"]);
        $clone->save();
        $clone->unsetRelation("cuotas");
        $clone->cuotas()->saveMany($credito->cuotas->map([$this, "cloneCuota"]));
        $clone->cuotas->each->setRelation("credito", $clone);
        $clone->unsetRelation("pagos_extras");
        $clone->pagosExtras()->saveMany($credito->pagosExtras->map(function($pagoExtra){
            return $pagoExtra->replicate();
        }));
        return $clone;
    }

    protected function getInteresDiferido($cuota){
        $diferido = BigRational::zero();
        if($cuota->saldo_capital->amount->isEqualTo("0")) return $diferido;

        $credito = $cuota->credito;
        while(($cuota = $cuota->anterior) != null){
            if($cuota->importe->amount->isGreaterThan("0")) break;
            $diferido = $diferido->plus(($cuota->anterior ? $cuota->anterior->saldo_capital : $credito->importe->minus($credito->cuota_inicial))->amount
                ->multipliedBy($cuota->dias)
                ->multipliedBy($credito->tasa_interes));
        }
        return $diferido->dividedBy("360");
    }

    /**
     * 
     * @param Credito $credito
     * @param string $importe
     * @param integer $periodo
     * @param integer $tipoAjuste
     * 
     */
    function apply($credito, $pagoExtra){
        $creditoOriginal = $credito;
        $credito = $this->cloneCredito($credito);
        $credito->pagosExtras()->save($pagoExtra);
        $cuota = $credito->cuotas->where("numero", $pagoExtra->periodo)->first();
        $saldo_capital = $cuota->saldo_capital->minus($pagoExtra->importe)->amount;
        $cuota->update([
            "pago_extra" => (string) $cuota->pago_extra->plus($pagoExtra->importe)->amount,
            "saldo" => (string)$cuota->saldo->plus($pagoExtra->importe)->amount,
            "saldo_capital" => (string) $saldo_capital,
        ]);
        
        if(($cuota = $cuota->siguiente) !== null){
            $this->tryApply($cuota, $pagoExtra);
        }

        // $pagosExtras = [$pagoExtra];

        // while(($cuota = $cuota->siguiente) !== null){
        //     foreach($pagosExtras as $pagoExtra){
        //         $this->tryApply($cuota, $pagoExtra);
        //     }

        //     $saldo_capital = $cuota->saldo_capital->amount->minus($cuota->pago_extra->amount);
        //     $cuota->fill([
        //         "saldo" => (string) $cuota->importe->amount->plus($cuota->getAttributes()["pago_extra"])
        //             ->minus($cuota->getAttributes()["total_pagos"])
        //             ->plus($cuota->getAttributes()["total_multas"]),
        //         "saldo_capital" => (string) $saldo_capital,
        //     ]);

        //     $pagosExtras = $cuota->pagosExtras;
        // }

        return $credito;
    }

    private function tryApply($cuota, $pagoExtra){
        if($this->canApply($pagoExtra->tipo_ajuste)){
            $this->applyImpl($cuota, $pagoExtra);
        }
        else {
            $this->next->tryApply($cuota, $pagoExtra);
        }
    }

    /**
     * @param integer $tipoAjuste
     * 
     * @return boolean
     */
    abstract protected function canApply($tipoAjuste);

    abstract protected function applyImpl(Cuota $cuota, PagoExtra $pagoExtra);
}