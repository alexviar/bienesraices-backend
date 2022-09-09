<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoCuota extends Model
{
    use HasFactory;

    protected $fillable = [
        "fecha",
        "importe"
    ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];
}
