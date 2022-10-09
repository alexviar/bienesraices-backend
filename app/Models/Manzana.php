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

    protected $hidden = ["proyecto", "plano"];

    function plano(){
        return $this->belongsTo(Plano::class);
    }

    function getProyectoAttribute(){
        return $this->plano->proyecto;
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
