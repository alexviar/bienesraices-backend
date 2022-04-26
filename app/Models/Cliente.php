<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory, SaveToUpper;

    protected $appends = [ "nombre_completo" ];

    protected $fillable = [
        "tipo",
        "tipo_documento",
        "numero_documento",
        "apellido_paterno",
        "apellido_materno",
        "nombre",
        "telefono"
    ];

    function getNombreCompletoAttribute(){
        $apellidos = "";
        if($this->apellido_materno) $apellidos .= $this->apellido_paterno." ";
        if($this->apellido_materno) $apellidos .= $this->apellido_materno." ";
        return "$apellidos {$this->nombre}";
    }

    function getCodigosPago(){
        return $this->hasMany(CodigoPago::class);
    }
}
