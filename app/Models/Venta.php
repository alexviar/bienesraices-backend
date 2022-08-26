<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 *
 * @property Carbon $fecha
 * @property Money $importe
 * @property Money|null $cuota_inicial
 * @property Cuota[]|Collection $cuotas
 */
class Venta extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "tipo",
        "fecha",
        "moneda",
        "importe",
        "proyecto_id",
        "lote_id",
        "cliente_id",
        "vendedor_id",
        "reserva_id",
        "estado"
    ];

    protected $hidden = [ "currency" ];

    protected $appends = [ "formated_id", "manzana" ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    function getFormatedIdAttribute(){
        $tipo = $this->tipo == 1 ? "CON" : "CRE";
        $id = $this->id;
        // $id = str_pad($this->id, 20, "0", STR_PAD_LEFT);
        return "$tipo-$id";
    }

    function getImporteAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    // static function find($id){
    //     if(!is_numeric($id)){
    //         $tipo = Str::substr($id, 0, 3);
    //         $id = Str::substr($id, 3);
    //         switch($tipo){
    //             case "CON": return static::where("id", intval($id))->where("tipo", 1)->first();
    //             case "CRE": return static::where("id", intval($id))->where("tipo", 2)->first();
    //             default: return null;
    //         }
    //     }
    //     return parent::find($id);
    // }

    function reserva(){
        return $this->belongsTo(Reserva::class);
    }

    function cliente(){
        return $this->belongsTo(Cliente::class);
    }

    function vendedor(){
        return $this->belongsTo(Vendedor::class);
    }

    function lote(){
        return $this->belongsTo(Lote::class);
    }

    function getManzanaAttribute(){
        return $this->lote->manzana;
    }

    function proyecto(){
        return $this->belongsTo(Proyecto::class);
    }

    function credito(){
        return $this->morphOne(Credito::class, "creditable")->ofMany(["id" => "max"], function($query){
            $query->whereEstado(1);
        });
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }

    function getReferencia(){
        return "Venta N.º {$this->id}";
    }

    function getCurrency(){
        return $this->currency;
    }

    /**
     * Get all of the appendable values that are arrayable.
     *
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (! count($this->appends)) {
            return [];
        }

        return $this->getArrayableItems(
            array_combine($this->appends, array_map(function($append){
                return $this->mutateAttributeForArray($append, null);
            }, $this->appends))
        );
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        // If an attribute is a date, we will cast it to a string after converting it
        // to a DateTime / Carbon instance. This is so we will get some consistent
        // formatting while accessing attributes vs. arraying / JSONing a model.
        $attributes = $this->addDateAttributesToArray(
            $attributes = $this->getArrayableAttributes()
        );

        $attributes = $this->addMutatedAttributesToArray(
            $attributes, $mutatedAttributes = $this->getMutatedAttributes()
        );

        // Next we will handle any casts that have been setup for this model and cast
        // the values to their appropriate type. If the attribute has a mutator we
        // will not perform the cast on those attributes to avoid any confusion.
        $attributes = $this->addCastAttributesToArray(
            $attributes, $mutatedAttributes
        );

        return $attributes + $this->getArrayableAppends();
    }

    private function getArrayableItemsRecursive($values, $visible, $hidden){
        $output = [];
        foreach($values as $k => $v) {
            if(!in_array($k, $hidden)){
                if(empty($visible) || in_array($k, $visible)){
                    $output[$k] = $v;
                }
                else {
                    if($v instanceof Arrayable){
                        $v = $v->toArray();
                    }
                    if(!is_array($v)) continue;
                    $key = null;
                    if(key_exists($k, $visible)){
                        $key = $k;
                    }
                    else if(key_exists("*", $visible)){
                        $key = "*";
                    }
                    if(isset($key)){
                        $output[$k] = collect($this->getArrayableItemsRecursive($v, $visible[$key]??[], $hidden[$key]??[]));
                    }
                }
            }
        }
        return $output;
    }

    protected function getArrayableItems(array $values)
    {
        return $this->getArrayableItemsRecursive($values, $this->getVisible(), $this->getHidden());
    }
}
