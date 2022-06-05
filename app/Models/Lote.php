<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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

    protected $hidden = ["reserva","venta"];
    
    protected $appends = [
        // "precio",
        "precio_sugerido"
    ];

    function getPrecioAttribute($value){
        return $value ? new Money($value, $this->manzana->proyecto->currency) : null;
    }

    function getPrecioSugeridoAttribute(){
        return $this->manzana->proyecto->precio_mt2->multipliedBy($this->superficie)->round(2);
    }

    function getEstadoAttribute($value){
        if($value == 1){
            if($this->reserva){
                return [
                    "code" => 3,
                    "message" => "Reservado"
                ];
            }
            if($this->venta){
                return [
                    "code" => 4,
                    "message" => "Vendido"
                ];
            }
            return [
                "code" => 1,
                "message" => "Disponible"
            ];
        }

        if($value == 2) {
            return [
                "code" => 2,
                "message" => "No disponible"
            ];
        }

        return [
            "code" => $value,
            "text" => "Desconocido"
        ];
    }

    function reserva(){
        // return $this->hasOne(Reserva::class)->where("estado", 1)->where("vencimiento", ">=", DB::raw("NOW()"))->orderBy("id");
        //Refactor por propositos de testing (travelTo)
        return $this->hasOne(Reserva::class)->where("estado", 1)->where("vencimiento", ">=", Carbon::now()->format("Y-m-d"))->orderBy("id");
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
