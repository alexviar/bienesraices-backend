<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * @property string $superficie
 * @property Money $precio 
 * @property Money $precio_sugerido 
 * @property Manzana $manzana
 */
class Lote extends Model
{
    use HasFactory;
    
    protected $appends = [
        "precio",
        "precio_sugerido"
    ];

    function getPrecioAttribute($value){
        return $value ? new Money($value, $this->manzana->proyecto->currency) : null;
    }

    function getPrecioSugeridoAttribute(){
        return $this->manzana->proyecto->precio_mt2->multipliedBy($this->superficie)->round(2);
    }

    function getEstadoAttribute($value){
        switch($value){
            case 1: 
                if($this->reserva){
                    return "Reservado";
                }
                if($this->venta){
                    return "Vendido";
                }
                return "Disponible";
            case 2:
                return "No disponible";
        }
    }

    function reserva(){
        return $this->hasOne(Reserva::class)->where("estado", 1)->where("vencimiento", ">=", DB::raw("NOW()"))->orderBy("id");
    }

    function venta(){
        return $this->hasOne(Venta::class)->where("estado", 1)->orderBy("id");
    }

    function manzana(){
        return $this->belongsTo(Manzana::class);
    }

    function getProyectoAttribute(){
        return $this->manzana->proyecto;
    }
}
