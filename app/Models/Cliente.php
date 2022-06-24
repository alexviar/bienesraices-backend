<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory, SaveToUpper;

    protected $appends = [ "nombre_completo", "documento_identidad" ];
    protected $hidden = [ "numero_documento", "tipo_documento" ];

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
        if($this->apellido_paterno) $apellidos .= $this->apellido_paterno." ";
        if($this->apellido_materno) $apellidos .= $this->apellido_materno." ";
        return "$apellidos {$this->nombre}";
    }

    function getDocumentoIdentidadAttribute(){
        return [
            "numero"=>$this->numero_documento,
            "tipo"=>$this->tipo_documento,
            "tipo_text" => $this->tipo_documento == 1 ? "CI" : ($this->tipo_documento == 2 ? "NIT" : "")
        ];
    }

    function getCodigoPagoAttribute(){
        return  "CLI-{$this->id}";
    }

    // function getCodigoPago($proyecto_id){
    //     $cp = $this->codigosPago->where("proyecto_id", $proyecto_id)->first();
    //     return $cp ? $cp->codigo : $this->codigo_pago;
    // }

    function codigosPago(){
        return $this->hasMany(CodigoPago::class);
    }

    function creditosEnMora(){
        return $this->hasMany(Venta::class)->where("tipo", 2)->where("estado", 1)->whereHas("cuotasVencidas");
    }
}
