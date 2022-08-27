<?php

namespace App\Models\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class DisminucionPlazo extends ProgramadorPagoExtra {
    function canApply($tipoAjuste)
    {
        return $tipoAjuste == 2;
    }

    function applyImpl($cuota, $pagoExtra)
    {
        $saldo_capital = $cuota->anterior->saldo_capital->amount;
        $pago = $cuota->credito->importe_cuotas->amount;
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
                "saldo" => (string) $cuota->saldo->minus($cuota->importe)->amount->plus($pagoCuota),
                "saldo_capital" => (string) $saldo_capital,
            ]);
            $diferido = BigDecimal::zero();
            $cuota = $cuota->siguiente;
        }
    }
}