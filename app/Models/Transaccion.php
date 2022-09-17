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
        "cliente_id",
        "user_id"
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    function getImporteAttribute($value){
        return new Money($value??"0", $this->currency);
    }

    #region Relationships
    /**
     * @return HasMany
     */
    function detalles(){
        return $this->hasMany(DetalleTransaccion::class);
    }

    /**
     * @return HasMany
     */
    function detallesPago(){
        return $this->hasMany(DetallePago::class);
    }
    /**
     * @return BelongsTo
     */
    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }
    #endregion
}
