<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleTransaccion extends Model
{
    use HasFactory;

    protected $table = "detalles_transaccion";

    function getImporteAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function transactable(){
        return $this->morphTo();
    }

    function transaccion(){
        return $this->belongsTo(Transaccion::class);
    }
}
