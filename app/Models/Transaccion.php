<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaccion extends Model
{
    use HasFactory;

    protected $table = "transacciones";

    protected $fillable = [
        "fecha",
        "moneda",
        "importe",
        "metodo_pago",
        "observaciones",
        "cliente_id",
        "deposito_id",
        "transactable_id",
        "transactable_type",
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    function getImporteAttribute($value){
        return new Money($value, $this->currency);
    }

    // /**
    //  * @return HasMany
    //  */
    // function detalles(){
    //     return $this->hasMany(DetalleTransaccion::class);
    // }

    function transactable(){
        return $this->morphTo();
    }

    /**
     * @return BelongsTo
     */
    function deposito(){
        return $this->belongsTo(Deposito::class);
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
}
