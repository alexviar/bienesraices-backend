<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

/**
 * @property Money $importe
 * @property Money $saldo
 */
class Deposito extends Model
{
    use HasFactory;

    protected $fillable = [
        "fecha",
        "numero_transaccion",
        "comprobante",
        "moneda",
        "importe",
        "saldo",
        "cliente_id"
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];
    #region Mutators
    function getImporteAttribute($value){
        return new Money($value, $this->currency);
    }

    function getSaldoAttribute($value){
        return new Money($value, $this->currency);
    }
    #endregion

    #region Relationships
    /**
     * @return BelongsTo
     */
    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
    #endregion

    protected function recalcularSaldo(){
        $transacciones = $this->transacciones;
        $groups = array_reduce($transacciones, function($carry, $pago){
            $monedaTransaccion = $pago->moneda;
            $importeTransaccion = $pago->importe;
            if(!Arr::has($carry, $monedaTransaccion)){
                $carry[$monedaTransaccion] = $importeTransaccion;
            }
            else{
                $carry[$monedaTransaccion] = $carry[$monedaTransaccion]->plus($importeTransaccion);
            }
            return $carry;
        }, []);

        $totalPagos = new Money("0.00", $this->currency);
        foreach($groups as $money){
            $totalPagos = $totalPagos->plus($money->exchangeTo($this->currency()));
        }
        
        $this->attributes["saldo"] = $this->saldo->minus($totalPagos->round(2));
    }
}
