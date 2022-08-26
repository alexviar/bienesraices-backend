<?php

namespace App\Models\Services;

use App\Models\Cuota;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class SoloInteres extends ProgramadorPagoExtra {
    function canApply($tipoAjuste)
    {
        return $tipoAjuste == 3;
    }

    /**
     * @param Cuota $cuota
     */
    function applyImpl($cuota, $pagoExtra)
    {
        $saldo_capital = $cuota->anterior->saldo_capital->amount;
        $diferido = $this->getInteresDiferido($cuota);

        while($cuota !== null){
            $fas = $cuota->fas;
            if($saldo_capital->isGreaterThan($cuota->saldo_capital->amount)){
                $pago = $cuota->importe->amount;
                $saldoMasInteres = $fas->multipliedBy($saldo_capital)->plus($diferido)->toScale(2, RoundingMode::HALF_UP);
                $pagoCuota = $pago->isGreaterThan($saldoMasInteres->minus("0.99")) || 
                    $cuota->siguiente == null ? $saldoMasInteres : $pago;
                $pagoCuota = $pagoCuota->plus($diferido->toScale(2, RoundingMode::HALF_UP));
                $saldo_capital = $saldoMasInteres->minus($pagoCuota);
            } 
            else{
                $pagoCuota = $cuota->fas->minus("1")->multipliedBy($saldo_capital)->plus($diferido)->toScale(2, RoundingMode::HALF_UP);
            }
            $cuota->update([
                "importe" => (string) $pagoCuota,
                "saldo" => (string) $pagoCuota//->plus($cuota->getAttributes()["pago_extra"])
                    ->minus($cuota->total_pagos->amount)
                    ->plus($cuota->total_multas->amount),
                "saldo_capital" => (string) $saldo_capital,
            ]);
            $diferido = BigDecimal::zero();
            $cuota = $cuota->siguiente;
        }
    }
}