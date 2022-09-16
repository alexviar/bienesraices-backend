<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Account de los clientes
 */
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        "balance",
        "moneda",
        "cliente_id",
    ];

    
    function getBalanceAttribute($value){
        return new Money($value, $this->currency);
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
}
