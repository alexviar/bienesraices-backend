<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 * @property integer id
 * @property string nombre
 * @property string socio
 * @property Point ubicacion
 * @property string moneda
 * @property integer $redondeo
 * @property Money $precio_mt2
 * @property Money $precio_reserva
 * @property Money $cuota_inicial
 * @property string $tasa_interes
 */
class Proyecto extends Model
{
    use HasFactory, SaveToUpper, SpatialTrait;

    protected $fillable = [
        "nombre",
        "socio",
        "ubicacion",

        "moneda",
        "redondeo",
        "precio_mt2",
        "precio_reserva",
        "cuota_inicial",
        "tasa_interes"
    ];

    protected $spatialFields = [
        'ubicacion'
    ];

    protected $hidden = ["currency", "lotes"];

    protected $appends = ["lotes_summary"];

    public function getLotesSummaryAttribute() {
        return [
            "total" => $this->lotes->count(),
            "disponibles" => $this->lotes->where("estado.code", 1)->count()
        ];
    }

    public function lotes(){
        return $this->hasManyThrough(Lote::class, Manzana::class);
    }

    public function getPrecioMt2Attribute($value)
    {
        return new Money($value, $this->currency);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, "moneda");
    }

    // public function getUbicacionAttribute($ubicacion){
    //     $byteOrder = $ubicacion[0];

    //     $format = $byteOrder === "\x00" ? "E" : "e";
    //     return unpack($format."longitud/".$format."latitud", Str::substr($ubicacion, 5));
    // }

    // public function setUbicacionAttribute($ubicacion) {
    //     $this->attributes["ubicacion"] = pack("cNE2",1,1, +$ubicacion["longitud"], +$ubicacion["latitud"]);
    // }

    function toArray()
    {
        $array = parent::toArray();
        $array["precio_mt2"] = $this->attributes["precio_mt2"];
        $array["ubicacion"] = [
            "latitud" => $this->ubicacion->getLat(),
            "longitud" => $this->ubicacion->getLng()
        ];
        return $array;
    }
}
