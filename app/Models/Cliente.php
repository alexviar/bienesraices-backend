<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Cliente extends Model
{
    use HasFactory, SaveToUpper;

    protected $appends = [ "nombre_completo", "documento_identidad", "codigo_pago" ];
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
        return "$apellidos{$this->nombre}";
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

    function codigosPagoLegacy(){
        return $this->hasMany(CodigoPago::class);
    }

    function creditosEnMora(){
        // return $this->hasMany(Credito::class)->with("creditable")->whereHasMorph([Venta::class], function($query){
        //     $query->where("estado", 1);
        // })->whereHas("cuotasVencidas");
        return $this->hasMany(Venta::class)->whereHas("credito", function($query){
            $query->whereHas("cuotasVencidas");
        });
    }

    function getCreditosAttribute(){
        return Credito::whereHasMorph([Venta::class], function($query){
            $query->where("estado", 1)->where("cliente_id", $this->id);
        })->get();
    }

    /**
     * @return Cliente|null
     */
    static function findByCodigoPago($codigoPago) {
        if(Str::startsWith($codigoPago, "CLI-")){
            return self::find(Str::after($codigoPago, "CLI-"));
        }
        return self::whereHas("codigosPagoLegacy", function($query) use($codigoPago){
            $query->where("codigo", $codigoPago);
        })->first();
    }
}
