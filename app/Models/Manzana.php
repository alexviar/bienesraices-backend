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

    protected $hidden = ["proyecto"];

    function proyecto(){
        return $this->belongsTo(Proyecto::class);
    }
}
