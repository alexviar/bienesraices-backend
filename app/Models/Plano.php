<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plano extends Model
{
    use HasFactory;

    protected $attributes = [
        "estado" => 1
    ];

    protected $fillable = [
        "titulo",
        "descripcion",
        "estado"
    ];

    #region Accessors

    public function getIsVigenteAttribute()
    {
        return ($this->estado&1) == 1;
    }

    // public function getIsObsoletoAttribute()
    // {
    //     return ($this->estado&1) == 0;
    // }

    public function getIsLockedAttribute()
    {
        return ($this->estado&2) == 2;
    }

    // public function getIsUnlockedAttribute()
    // {
    //     return ($this->estado&2) == 1;
    // }
    #endregion

    #region Mutators
    public function setIsVigenteAttribute($value)
    {
        if($value){
            $this->attributes["estado"] |= 1;
        }
        else{
            $this->attributes["estado"] &= 0xFE;
        }
    }

    public function setIsLockedAttribute($value)
    {
        if($value){
            $this->attributes["estado"] |= 2;
        }
        else{
            $this->attributes["estado"] &= 0xFD;
        }
    }
    #endregion

    #region Relationships
    /**
     * @return BelongsTo
     */
    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class);
    }
    #endregion
}
