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
        $saldo_capital_objetivo = $cuota->anterior->saldo_capital->amount;
        $saldo_capital = $saldo_capital_objetivo->plus($pagoExtra->importe->amount);
        $pago = $this->getImporteCuotas($cuota);
        $diferido = $this->getInteresDiferido($cuota);

        while($cuota !== null){
            $fas = $cuota->fas;
            $saldoMasInteres = $fas->multipliedBy($saldo_capital)->toScale(2, RoundingMode::HALF_UP);
            $pagoCuota = $pago->isGreaterThan($saldoMasInteres->minus("0.99")) 
                || $cuota->siguiente == null ? 
                    $saldoMasInteres : 
                    $pago;
            $saldo_capital = $saldoMasInteres->minus($pagoCuota);
            if($saldo_capital_objetivo->isGreaterThan($saldo_capital)){

                $cuota->fill([
                    "importe" => (string )$pagoCuota,
                    "saldo_capital" => (string) $saldo_capital,
                ]);
            } 
            else{
                $pagoCuota = $cuota->fas->minus("1")->multipliedBy($saldo_capital);
                $cuota->fill([
                    "importe" => (string) $pagoCuota->plus($diferido)->toScale(2, RoundingMode::HALF_UP),
                    "saldo_capital" => (string) $saldo_capital_objetivo,
                ]);
            }
            $diferido = BigDecimal::zero();
            $cuota = $cuota->siguiente;
        }
    }
}