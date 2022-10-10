<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoriaLote extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "codigo",
        "descripcion",
        "precio_m2",
        "proyecto_id"
    ];

    protected $hidden = [
        "proyecto"
    ];

    function getPrecioM2Attribute($value){
        return $value ? new Money($value, $this->proyecto->currency) : null;
    }

    #region Relationships
    /**
     * @return BelongsTo
     */
    function proyecto(){
        return $this->belongsTo(Proyecto::class);
    }
    #endregion
}
