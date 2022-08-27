<?php

namespace App\Models\Services;

use App\Models\Cuota;
use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;

class Diferido extends ProgramadorPagoExtra {
    function canApply($tipoAjuste)
    {
        return $tipoAjuste == 4;
    }

    /**
     * @param Cuota $cuota
     */
    function applyImpl($cuota, $pagoExtra)
    {
        $saldo_capital = $cuota->anterior->saldo_capital->amount;
        $diferido = $this->getInteresDiferido($cuota);

        while($cuota !== null){
            if($saldo_capital->isGreaterThan($cuota->saldo_capital->amount)){
                $pago = $cuota->importe->amount;
                $fas = $cuota->fas;
                $saldoMasInteres = $fas->multipliedBy($saldo_capital)->plus($diferido)->toScale(2, RoundingMode::HALF_UP);
                $pagoCuota = $pago->isGreaterThan($saldoMasInteres->minus("0.99")) 
                    || $cuota->siguiente == null ? 
                        $saldoMasInteres : 
                        $pago;
                $pagoCuota = $pagoCuota->plus($diferido->toScale(2, RoundingMode::HALF_UP));

                $saldo_capital = $saldoMasInteres->minus($pagoCuota);

                $diferido = BigRational::zero();
            } 
            else{
                $diferido = $diferido->plus($cuota->fas->minus("1")->multipliedBy($saldo_capital));
                $pagoCuota = BigDecimal::zero();
            }
            $cuota->update([
                "importe" => $pagoCuota,
                "saldo" => (string) $cuota->saldo->minus($cuota->importe)->amount->plus($pagoCuota),
                "saldo_capital" => (string) $saldo_capital,
            ]);
            $cuota = $cuota->siguiente;
        }
    }
}