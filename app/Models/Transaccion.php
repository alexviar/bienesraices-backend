<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaccion extends Model
{
    use HasFactory;

    protected $table = "transacciones";

    protected $fillable = [
        "fecha",
        "comprobante",
        "importe",
        "moneda",
        "forma_pago"
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    /**
     * @return HasMany
     */
    function detalles(){
        return $this->hasMany(DetalleTransaccion::class);
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
}
