<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Anulacion extends Model
{
    use HasFactory;

    protected $fillable = [
        "fecha",
        "motivo",
        "anulable_type",
        "anulable_id"
    ];

    protected $table = "anulaciones";

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    #region Relationships
    function anulable()
    {
        return $this->morphTo();
    }
    #endregion
}
