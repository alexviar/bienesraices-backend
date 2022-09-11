<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 * @property Money importe
 * 
 * @method static Reserva|null find($id)
 */
class Reserva extends Model
{
    use HasFactory;

    protected $fillable = [
        "fecha",
        "proyecto_id",
        "lote_id",
        "vendedor_id",
        "cliente_id",
        "moneda",
        "importe",
        "saldo_credito",
        "saldo_contado",
        "vencimiento"
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d",
        "vencimiento" => "date:Y-m-d"
    ];

    function getImporteAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function getSaldoCreditoAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function getSaldoContadoAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function getReferencia(){
        return "Reserva N.º {$this->id}";
    }

    function getCurrency(){
        return $this->currency;
    }

    function cliente(){
        return $this->belongsTo(Cliente::class);
    }

    function vendedor(){
        return $this->belongsTo(Vendedor::class);
    }

    function lote(){
        return $this->belongsTo(Lote::class);
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
}
