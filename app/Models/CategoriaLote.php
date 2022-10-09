<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaLote extends Model
{
    use HasFactory;

    protected $fillable = [
        "codigo",
        "descripcion",
        "precio_m2",
        "proyecto_id"
    ];
}
