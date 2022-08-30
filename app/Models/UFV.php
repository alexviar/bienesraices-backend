<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UFV extends Model
{
    use HasFactory;

    protected $table = "ufv";

    protected $fillable = [
        "fecha",
        "valor"
    ];
}
