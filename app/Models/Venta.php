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
        "estado",

        "cuota_inicial",
        "tasa_interes",
        "plazo",
        "periodo_pago",
        "dia_pago",
        "tasa_mora"
    ];

    protected $hidden = [ "currency" ];

    protected $appends = [ "formated_id", "url_plan_pago", "manzana" ];

    protected $casts = [
        "fecha" => "date:Y-m-d"
    ];

    function getUrlPlanPagoAttribute(){
        return route("ventas.plan_pago", [
            "proyectoId" => $this->proyecto_id,
            "id" => $this->id
        ]);
    }

    function getFormatedIdAttribute(){
        $tipo = $this->tipo == 1 ? "CON" : "CRE";
        $id = $this->id;
        // $id = str_pad($this->id, 20, "0", STR_PAD_LEFT);
        return "$tipo-$id";
    }

    function getImporteAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function getCuotaInicialAttribute($value){
        return $value ? new Money($value, Currency::find($this->moneda)) : null;
    }

    function getPeriodoPagoTextAttribute(){
        switch($this->periodo_pago){
            case 1: return "Mensual";
            case 2: return "Bimestral";
            case 3: return "Trimestral";
            case 6: return "Semestral";
            default: return "Inválido";
        }
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

    function cuotas(){
        return $this->hasMany(Cuota::class);
    }

    function cuotasVencidas(){
        return $this->cuotas()->where("saldo", ">", "0")->whereDate("vencimiento", "<", Carbon::now()->toDateString());
    }

    function getTotalCreditoAttribute(){
        //TODO: Aplicar un mecanismo equivalente a useMemo en React
        return $this->cuotas->reduce(function($total, $cuota){
            return $total->plus($cuota->importe);
        }, new Money("0", $this->currency))->plus($this->cuota_inicial);
    }

    function getTotalInteresesAttribute(){
        return $this->total_credito->minus($this->importe);
    }

    function currency(){
        return $this->belongsTo(Currency::class, "moneda");
    }

    function getReferencia(){
        return $this->tipo == 1 ? "Venta N.º {$this->id}" : "Cuota inicial de la venta N.º {$this->id}";
    }

    function getCurrency(){
        return $this->currency;
    }

    // /**
    //  * Calcula el factor de recuperacion del capital
    //  *
    //  * @param Carbon $fecha
    //  * @param ?Carbon $fechaPrimerCuota
    //  */
    // static function getFRC(
    //     $fecha,
    //     $tasaInteres,
    //     $numeroCuotas,
    //     $periodoPago,
    //     $diaPago=null
    // ) {
    //     $fechaPrimerCuota = $diaPago ? Carbon::createFromDate($fecha->year, 1, $diaPago)->startOfDay() : null;
    //     $tasaInteres = BigDecimal::of($tasaInteres);
    //     if(!$fechaPrimerCuota){
    //         $tasaInteres = $tasaInteres->multipliedBy($periodoPago)->dividedBy(12, 10, RoundingMode::HALF_UP);
    //         return $tasaInteres->dividedBy(BigDecimal::one()->minus(BigDecimal::one()->dividedBy(BigDecimal::one()->plus($tasaInteres)->power($numeroCuotas), 10, RoundingMode::HALF_UP)), 10, RoundingMode::HALF_UP);
    //     }
    //     $offset = ($fecha->daysInMonth - $fecha->day + $diaPago) < 21 ? $fecha->month : $fecha->month - 1;
    //     $fas = [];
    //     $current = $fecha;
    //     for($k = 0; $k < $numeroCuotas; $k++){
    //         // if($fechaPrimerCuota){
    //             $next = $fechaPrimerCuota->copy()->addMonthsNoOverflow($periodoPago*($k+1)+$offset);
    //             $daysDiff = $next->diffInDays($current);
    //             $fas[] = $tasaInteres->multipliedBy($daysDiff)->dividedBy(360, 10, RoundingMode::HALF_UP)->plus(1);
    //             $current = $next->copy();
    //         // }
    //         // else {
    //         //     $fas[] = $tasaInteres->multipliedBy($periodoPago)->dividedBy(12, 10, RoundingMode::HALF_UP)->plus(1);
    //         // }
    //     }

    //     $numerator = BigDecimal::one();
    //     $denominator = BigDecimal::zero();
    //     for($i = $numeroCuotas-1; $i >=0; $i--){
    //         $denominator = $denominator->plus($numerator);
    //         $numerator = $numerator->multipliedBy($fas[$i]);
    //     }
    //     return $numerator->dividedBy($denominator, 10, RoundingMode::HALF_UP);
    // }

    function crearPlanPago(){
        $builder = new PlanPagosBuilder(
            $this->fecha,
            $this->importe->minus($this->cuota_inicial)->amount,
            BigDecimal::of($this->tasa_interes),
            $this->plazo,
            $this->periodo_pago,
            $this->dia_pago
        );
        $cuotas = $builder->build();
        foreach($cuotas as $cuota){
            $this->cuotas()->create([
                "numero"=>$cuota["numero"],
                "vencimiento" => $cuota["vencimiento"],
                "importe" => (string) $cuota["pago"],
                "saldo" => (string) $cuota["pago"],
                "saldo_capital" => (string) $cuota["saldo"]
            ]);
        }
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
