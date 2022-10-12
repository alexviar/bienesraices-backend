<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * 
 * @property integer id
 * @property string nombre
 * @property string socio
 * @property Point ubicacion
 * @property string moneda
 * @property integer $redondeo
 * @property Money $precio_mt2
 * @property Money $precio_reservas
 * @property Money $cuota_inicial
 * @property string $tasa_interes
 * 
 * @method static Proyecto find(integer $id)
 */
class Proyecto extends Model
{
    use HasFactory, SaveToUpper, SpatialTrait;

    protected $fillable = [
        "nombre",
        // "socio",
        "ubicacion",

        "moneda",
        "redondeo",
        "precio_reservas",
        "duracion_reservas",
        "cuota_inicial",
        "tasa_interes",
        "tasa_mora",
    ];

    protected $spatialFields = [
        'ubicacion'
    ];

    protected $hidden = ["currency", "plano"];

    protected $appends = ["lotes_summary", "clientes_en_mora"];

    public function getPrecioMt2Attribute($value)
    {
        return new Money($value, $this->currency);
    }

    public function getPrecioReservasAttribute($value)
    {
        return new Money($value, $this->currency);
    }

    public function getCuotaInicialAttribute($value)
    {
        return new Money($value, $this->currency);
    }

    public function getLotesSummaryAttribute() {
        return [
            "total" => $this->plano ? $this->plano->lotes->count() : 0,
            "disponibles" => $this->plano ? $this->plano->lotes->where("estado.code", 1)->count() : 0
        ];
    }

    public function getClientesEnMoraAttribute() {
        return Cliente::whereHas("creditosEnMora", function($query){
            $query->where("proyecto_id", $this->id);
        })->count();
    }

    #region Relationships
    /**
     * @return HasOne
     */
    public function plano()
    {
        return $this->hasOne(Plano::class)->ofMany([
            "id" => "MAX"
        ], function($query){
            $query->whereRaw("(`estado` & 1) = 1");
        });
    }

    /**
     * @return HasMany
     */
    public function planos()
    {
        return $this->hasMany(Plano::class);
    }
    
    public function categorias(){
        return $this->hasMany(CategoriaLote::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, "moneda");
    }
    #endregion


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
        $array["ubicacion"] = [
            "latitud" => $this->ubicacion->getLat(),
            "longitud" => $this->ubicacion->getLng()
        ];
        return $array;
    }
}
