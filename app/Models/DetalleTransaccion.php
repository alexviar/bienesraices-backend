<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleTransaccion extends Model
{
    use HasFactory;

    protected $fillable = [
        "moneda",
        "importe",
        "referencia",
        "pagable_id",
        "pagable_type"
    ];

    protected $appends = [
        "importe_moneda_transaccion"
    ];

    protected $table = "detalles_transaccion";

    function getImporteAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function getImporteMonedaTransaccionAttribute()
    {
        return $this->importe->round(2)->exchangeTo($this->transaccion->moneda);
    }

    function transaccion(){
        return $this->belongsTo(Transaccion::class);
    }

    // function reservas(){
    //     return $this->morphedByMany(Reserva::class, "transactable");
    // }

    // function ventas(){
    //     return $this->morphedByMany(Venta::class, "transactable");
    // }
    
    // function creditos(){
    //     return $this->morphedByMany(Credito::class, "transactable");
    // }
    
    // function cuotas(){
    //     return $this->morphedByMany(Cuota::class, "transactable");
    // }

    function pagable(){
        return $this->morphTo();
    }
}
