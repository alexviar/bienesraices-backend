<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetallePago extends Model
{
    use HasFactory;

    protected $table = "detalles_pagos";

    protected $fillable = [
        "importe",
        "forma_pago"
    ];
}
