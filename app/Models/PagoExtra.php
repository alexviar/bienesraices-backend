<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        "tipo_ajuste",
        "importe",
        "periodo",
        "credito_id"
    ];

    protected $table = "pagos_extras";

    function getImporteAttribute($value){
        return new Money($value, $this->credito->getCurrency()->code);
    }

    function credito(){
        return $this->belongsTo(Credito::class);
    }
}
