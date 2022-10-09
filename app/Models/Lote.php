<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Brick\Math\RoundingMode;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 
 * @property string $numero
 * @property string $superficie
 * @property Polygon $geocerca
 * @property Money $precio 
 * @property Money $precio_sugerido 
 * @property Manzana $manzana
 */
class Lote extends Model
{
    use HasFactory,
        SpatialTrait;

    protected $fillable = [
        "numero",
        "superficie",
        "geocerca",
        "precio",
        "manzana_id",
        "categoria_id"
    ];

    protected $spatialFields = [
        "geocerca"
    ];

    protected $hidden = ["reserva","venta"];
    
    protected $appends = [
        // "precio",
        "precio_sugerido"
    ];

    function getPrecioAttribute($value){
        return $value ? new Money($value, $this->manzana->proyecto->currency) : null;
    }

    function getPrecioSugeridoAttribute(){
        $precioSugerido = $this->manzana->proyecto->precio_mt2->multipliedBy($this->superficie);
        if($this->proyecto->redondeo){
            $precioSugerido = $precioSugerido->mround($this->proyecto->redondeo, RoundingMode::UP);
        }
        return $precioSugerido->round(2);
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
        //Refactor para propositos de testing (travelTo)
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

    function toArray()
    {
        $array = parent::toArray();
        $array["geocerca"] = $this->geocerca ? array_map(function(Point $point) {
            return [
                "latitud" => $point->getLat(),
                "longitud" => $point->getLng(),
            ];
        }, $this->geocerca->getLineStrings()[0]->getPoints()) : [];

        return $array;
    }
}
