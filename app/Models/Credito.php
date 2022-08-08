<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Cuota[]|Collection $cuotas
 */
class Credito extends Model
{
    use HasFactory;

    /** @var Carbon $fechaDeConsulta */
    public $fechaDeConsulta;

    protected $fillable = [
        "cuota_inicial",
        "tasa_interes",
        "tasa_mora",
        "plazo",
        "periodo_pago",
        "dia_pago",
    ];

    function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->fechaDeConsulta = Carbon::today();
    }
    function getFechaAttribute(){
        return $this->creditable->fecha;
    }

    function getImporteAttribute(){
        return $this->creditable->importe;
    }

    function getCuotaInicialAttribute($value){
        return new Money($value, $this->creditable->getCurrency());
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

    function cuotasVencidas(){
        return $this->cuotas()->where("saldo", ">", "0")->whereDate("vencimiento", "<", Carbon::now()->toDateString());
    }

    function getCuotasPendientesAttribute(){
        $fecha = $this->fechaDeConsulta;
        $pendientes = [];
        $i = 0;
        do{
            $cuota = $this->cuotas[$i];
            if($cuota->saldo->amount->isGreaterThan(BigDecimal::zero()))
            {
                $pendientes[] = $cuota;
            }
            $i++;
        }while($fecha->isAfter($cuota->vencimiento) && $i < $this->cuotas->count());
        return $pendientes;
    }
    
    /**
     * @param Carbon
     */
    function setFechaDeConsulta($fecha){
        $this->fechaDeConsulta = $fecha;
    }

    function getTotalCreditoAttribute(){
        //TODO: Aplicar un mecanismo equivalente a useMemo en React
        return $this->cuotas->reduce(function($total, $cuota){
            return $total->plus($cuota->importe);
        }, new Money("0", $this->currency))->plus($this->cuota_inicial);
    }

    function getTotalInteresesAttribute(){
        return $this->total_credito->minus($this->creditable->importe);
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    function creditable(){
        return $this->morphTo();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    function cuotas(){
        return $this->hasMany(Cuota::class)->oldest("numero");
    }

    function pagosExtras(){
        return $this->hasMany(PagoExtra::class);
    }
    
    function build(){
        $builder = new PlanPagosBuilder(
            $this->creditable->fecha,
            $this->creditable->importe->minus($this->cuota_inicial)->amount,
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

    function getCurrency(){
        return $this->creditable->getCurrency();
    }

    function getReferencia(){
        return "Cuota inicial del crédito Nº {$this->id}";
    }
}
