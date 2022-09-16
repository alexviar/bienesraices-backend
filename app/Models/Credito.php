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

    protected $fillable = [
        "codigo",
        "importe_cuotas",
        "cuota_inicial",
        "tasa_interes",
        "tasa_mora",
        "plazo",
        "periodo_pago",
        "dia_pago",
    ];

    protected $hidden = ["creditable"];

    protected $appends = ["fecha", "importe", "url_plan_pago", "url_historial_pagos"];

    function getMorphKeyName()
    {
        return "codigo";
    }

    function getUrlPlanPagoAttribute(){
        return route("creditos.plan_pago", [
            "id" => $this->id
        ]);
    }

    function getUrlHistorialPagosAttribute(){
        return route("creditos.historial_pagos", [
            "id" => $this->id
        ]);
    }

    function getFechaAttribute(){
        return $this->creditable->fecha;
    }

    function getImporteAttribute(){
        return $this->creditable->importe_pendiente;
    }

    function getImporteCuotasAttribute($value){
        return new Money($value, $this->importe->currency);
    }

    function getCuotaInicialAttribute(){
        return $this->creditable->importe;
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
        $pendientes = [];
        $i = 0;
        do{
            $cuota = $this->cuotas[$i];
            if($cuota->saldo->round()->amount->isGreaterThan(BigDecimal::zero()))
            {
                $pendientes[] = $cuota;
            }
            $i++;
        }while($cuota->vencida && $i < $this->cuotas->count());
        return $pendientes;
    }

    function projectTo(Carbon $fecha){
        $this->cuotas->each->projectTo($fecha);
    }

    function getTotalCreditoAttribute(){
        //TODO: Aplicar un mecanismo equivalente a useMemo en React
        return $this->cuotas->reduce(function($total, $cuota){
            return $total->plus($cuota->importe)->plus($cuota->pago_extra);
        }, new Money("0", $this->getCurrency()))->plus($this->cuota_inicial);
    }

    function getTotalInteresesAttribute(){
        return $this->cuotas->reduce(function($total, $cuota){
            return $total->plus($cuota->interes);
        }, new Money("0", $this->getCurrency()));
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
            $this->fecha,
            $this->importe->amount,
            BigDecimal::of($this->tasa_interes),
            $this->plazo,
            $this->periodo_pago,
            $this->dia_pago
        );
        $cuotas = $builder->build();
        foreach($cuotas as $cuota){
            $this->cuotas()->create([
                "codigo" => $this->codigo*1000 + $cuota["numero"],
                "numero"=>$cuota["numero"],
                "vencimiento" => $cuota["vencimiento"],
                "importe" => (string) $cuota["pago"],
                "saldo" => (string) $cuota["pago"],
                "saldo_capital" => (string) $cuota["saldo"]
            ]);
        }
        $this->update([
            "importe_cuotas" => $cuotas[0]["pago"]
        ]);
    }    

    function getCurrency(){
        return $this->creditable->getCurrency();
    }

    function getReferencia(){
        return "Cuota inicial del crédito Nº {$this->codigo}";
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    function transacciones(){
        return $this->morphToMany(DetalleTransaccion::class, "transactable");
    }
}
