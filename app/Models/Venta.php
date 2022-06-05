<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 *
 * @property Carbon $fecha
 * @property Money $precio
 * @property Cuota[]|Collection $cuotas
 */
class Venta extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "tipo",
        "fecha",
        "moneda",
        "precio",
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
        "tasa_mora"
    ];

    protected $hidden = [ "currency" ];

    protected $appends = [ "formated_id", "url_plan_pago" ];

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

    function getPrecioAttribute($value){
        return new Money($value, Currency::find($this->moneda));
    }

    function getCuotaInicialAttribute($value){
        return $value ? new Money($value, Currency::find($this->moneda)) : null;
    }

    function getPeriodoPagoTextAttribute(){
        switch($this->periodo_pago){
            case 1: return "Mensual";
            case 2: return "Bimestral";
            case 4: return "Trimestral";
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

    function getTotalCreditoAttribute(){
        //TODO: Aplicar un mecanismo equivalente a useMemo en React
        return $this->cuotas->reduce(function($total, $cuota){
            return $total->plus($cuota->importe);
        }, new Money("0", $this->currency));
    }

    function getTotalInteresesAttribute(){
        return $this->total_credito->minus($this->precio);
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

    /**
     * Calcula el factor de recuperacion del capital
     *
     * @param Carbon $fecha
     * @param ?Carbon $fechaPrimerCuota
     */
    static function getFRC(
        $fecha,
        $tasaInteres,
        $numeroCuotas,
        $periodoPago,
        $fechaPrimerCuota=null
    ) {
        $tasaInteres = BigDecimal::of($tasaInteres);
        // if(!$fechaPrimerCuota){
        //     return $tasaInteres->dividedBy(BigDecimal::one()->minus(BigDecimal::one()->dividedBy(BigDecimal::one()->plus($tasaInteres)->power($numeroCuotas), 10, RoundingMode::HALF_UP)), 10, RoundingMode::HALF_UP);
        // }
        $tasaDiaria = $tasaInteres->dividedBy($periodoPago*30, 10, RoundingMode::HALF_UP);
        $fas = [];
        $current = $fecha;
        $next = $fechaPrimerCuota ??  $fecha->copy()->addDays($periodoPago*30);
        $capitalizacionCalendaria = !is_null($fechaPrimerCuota);
        for($k = 0; $k < $numeroCuotas; $k++){
            $daysDiff = $next->diffInDays($current);
            $fas[] = $tasaDiaria->multipliedBy($daysDiff)->plus(1);
            $current = $next->copy();
            if($capitalizacionCalendaria){
                $next->addMonths($periodoPago);
            }
            else {
                $next->addDays($periodoPago*30);
            }
        }

        $numerator = BigDecimal::one();
        $denominator = BigDecimal::zero();
        for($i = $numeroCuotas-1; $i >=0; $i--){
            $denominator = $denominator->plus($numerator);
            $numerator = $numerator->multipliedBy($fas[$i]);
        }
        return $numerator->dividedBy($denominator, 10, RoundingMode::HALF_UP);
    }

    function crearPlanPago(){
        $n = $this->plazo/$this->periodo_pago;
        $interesPeriodoPago = BigDecimal::of($this->tasa_interes)->multipliedBy($this->periodo_pago)->dividedBy(12, 10, RoundingMode::HALF_UP);
        $frc = static::getFRC(
            $this->fecha,
            $interesPeriodoPago,
            $n,
            $this->periodo_pago
        );

        $fechaPago = $this->fecha->copy()->addMonth();
        $saldoCapital = $this->precio->minus($this->cuota_inicial);
        $pagos = $saldoCapital->multipliedBy($frc)->round();
        for($i = 0; $i < $n; $i++){
            if($saldoCapital->amount->isEqualTo(BigDecimal::zero())) break;

            $interes = $saldoCapital->multipliedBy($interesPeriodoPago)->round();
            $saldoCapitalMasInteres = $saldoCapital->plus($interes);
            if($pagos->amount->isGreaterThan($saldoCapitalMasInteres->amount->minus("0.99")) || $i == $n -1){
                $pago = $saldoCapitalMasInteres;
            }
            else{
                $pago = $pagos;
            }

            $saldoCapital = $saldoCapitalMasInteres->minus($pago);
            $pago = (string) $pago->amount->toScale(2, RoundingMode::HALF_UP);
            $this->cuotas()->create([
                "numero"=>$i+1,
                "vencimiento" => $fechaPago,
                "importe" => $pago,
                "saldo" => $pago,
                "saldo_capital" => (string) $saldoCapital->amount->toScale(2, RoundingMode::HALF_UP)
            ]);
            $fechaPago = $fechaPago->copy()->addMonth();
        }
    }

    // function toArray()
    // {
    //     return [
    //         "id" => $this->formated_id,
    //     ] + parent::toArray();
    // }
}
