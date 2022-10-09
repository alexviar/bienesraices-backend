<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Proyecto $proyecto
 */
class Manzana extends Model
{
    use HasFactory;

    protected $fillable = [
        "numero",
        "proyecto_id"
    ];

    protected $hidden = ["proyecto"];

    function proyecto(){
        return $this->belongsTo(Proyecto::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function lotes(){
        return $this->hasMany(Lote::class);
    }

    function getTotalLotesAttribute(){
        return ($this->relationLoaded("lotes") ? $this->lotes : $this->lotes())->count();
    }
}
