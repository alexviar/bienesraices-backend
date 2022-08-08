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
        $pago = $this->getImporteCuotas($cuota);
        $diferido = $this->getInteresDiferido($cuota);

        while($cuota !== null){
            $fas = $cuota->fas;
            $saldoMasInteres = $fas->multipliedBy($saldo_capital)->toScale(2, RoundingMode::HALF_UP);
            $pagoCuota = $pago->isGreaterThan($saldoMasInteres->minus("0.99")) || 
                $cuota->siguiente == null ? $saldoMasInteres : $pago;

            $saldo_capital = $saldoMasInteres->minus($pagoCuota);            
        
            $cuota->fill([
                "importe" => (string) $pagoCuota->plus($diferido->toScale(2, RoundingMode::HALF_UP)),
                "saldo_capital" => (string) $saldo_capital,
            ]);
            $diferido = BigDecimal::zero();
            $cuota = $cuota->siguiente;
        }
    }
}