<?php

namespace App\Models\Services;

use App\Models\Credito;
use App\Models\Cuota;
use App\Models\PagoExtra;
use Brick\Math\BigRational;
use Illuminate\Support\Facades\Log;

abstract class ProgramadorPagoExtra {

    // private $next;

    function __construct(
        private ?ProgramadorPagoExtra $next=null
    ){ }

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

    protected function getImporteCuotas($cuota){
        $credito = $cuota->credito;
        // $saldoInicial = $credito->importe->minus($credito->cuota_inicial)->amount;
        // return $credito->cuotas[0]->frc->multipliedBy($saldoInicial);
        return $credito->cuotas[0]->importe->amount;
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
        $credito->cuotas->each->setRelation("credito", $credito);
        $cuota = $credito->cuotas->where("numero", $pagoExtra->periodo)->first();
        $saldo_capital = $cuota->saldo_capital->amount->minus($pagoExtra->importe);
        $cuota->fill([
            "pago_extra" => (string) $cuota->pago_extra->amount->plus($pagoExtra->importe),
            "saldo" => (string)$cuota->saldo->amount->plus($pagoExtra->importe),
            "saldo_capital" => (string) $saldo_capital,
        ]);

        $pagosExtras = [$pagoExtra];

        while(($cuota = $cuota->siguiente) !== null){
            foreach($pagosExtras as $pagoExtra){
                $this->tryApply($cuota, $pagoExtra);
            }

            $saldo_capital = $cuota->saldo_capital->amount->minus($cuota->pago_extra->amount);
            $cuota->fill([
                "saldo" => (string) $cuota->importe->amount->plus($cuota->getAttributes()["pago_extra"])
                    ->minus($cuota->getAttributes()["total_pagos"])
                    ->plus($cuota->getAttributes()["total_multas"]),
                "saldo_capital" => (string) $saldo_capital,
            ]);

            $pagosExtras = $cuota->pagosExtras;
        }

        return $pagoExtra;
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