<?php

namespace App\Models\Services;

use App\Models\PagoExtra;
use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

class Prorrateo extends ProgramadorPagoExtra {

    function canApply($tipoAjuste)
    {
        return $tipoAjuste == 1;
    }

    function getImporteCuotas($cuota, $saldoInicial)
    {
        return $cuota->frc->multipliedBy($saldoInicial)->toScale(2, RoundingMode::HALF_UP);
    }

    function applyImpl($cuota, $pagoExtra)
    {
        $saldo_capital = $cuota->anterior->saldo_capital->amount;
        $pago = $this->getImporteCuotas($cuota, $saldo_capital);
        $cuota->credito->update(["importe_cuotas" => (string) $pago]);
        $diferido = $this->getInteresDiferido($cuota);

        while($cuota !== null){
            $fas = $cuota->fas;
            $saldoMasInteres = $fas->multipliedBy($saldo_capital)->plus($diferido)->toScale(2, RoundingMode::HALF_UP);
            $pagoCuota = $pago->isGreaterThan($saldoMasInteres->minus("0.99")) || 
                $cuota->siguiente == null ? $saldoMasInteres : $pago;
            $pagoCuota = $pagoCuota->plus($diferido->toScale(2, RoundingMode::HALF_UP));

            $saldo_capital = $saldoMasInteres->minus($pagoCuota);
        
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