<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Saldo a favor de los clientes
 */
class Saldo extends Model
{
    use HasFactory;

    protected $table = "saldos";

    protected $fillable = [
        "cliente_id",
        "importe",
        "moneda"
    ];

    
    function getImporteAttribute($value){
        return new Money($value, $this->currency);
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
}
