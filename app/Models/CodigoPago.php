<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodigoPago extends Model
{
    use HasFactory;

    protected $table = "codigos_pago";

    function cliente(){
        return $this->belongsTo(Cliente::class);
    }

    function proyecto(){
        return $this->belongsTo(Proyecto::class);
    }
}
