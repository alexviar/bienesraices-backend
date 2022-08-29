<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    use HasFactory, SaveToUpper;

    protected $table = "vendedores";
    
    protected $appends = [ "nombre_completo" ];

    function getNombreCompletoAttribute(){
        $apellidos = "";
        if($this->apellido_paterno) $apellidos .= $this->apellido_paterno." ";
        if($this->apellido_materno) $apellidos .= $this->apellido_materno." ";
        return "$apellidos{$this->nombre}";
    }
}
