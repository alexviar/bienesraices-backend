<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleTransaccion extends Model
{
    use HasFactory;

    protected $table = "detalles_transaccion";

    function transactable(){
        return $this->morphTo();
    }
}
