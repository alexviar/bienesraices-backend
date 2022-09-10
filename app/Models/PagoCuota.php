<?php

namespace App\Models;

use App\Events\PagoCuotaCreated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCuota extends Model
{
    use HasFactory;

    protected $fillable = [
        "fecha",
        "moneda",
        "importe"
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    // protected $dispatchesEvents = [
    //     "created" => PagoCuotaCreated::class
    // ];

    #region Relationships
    /**
     * @return BelongsTo
     */
    function cuota(){
        return $this->belongsTo(Cuota::class);
    }
    #endregion
}
