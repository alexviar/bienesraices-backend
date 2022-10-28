<?php 

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;

class PlanPagosBuilder {
  const PERIODO_MENSUAL = 1;
  const PERIODO_BIMESTRAL = 2;
  const PERIODO_TRIMESTRAL = 3;
  const PERIODO_SEMESTRAL = 6;

  /** @var Carbon $fecha */
  protected $fecha;

  /** @var BigRational $importe */
  protected $importe;

  /** @var BigRational $tasaInteres */
  protected $tasaInteres;
  protected $plazo;
  protected $periodo;
  protected $diaPago;


  protected $offset;
  protected $numeroCuotas;

  /** @var Carbon $enero */
  protected $enero;

  /** @var BigDecimal $pago */
  protected $pago;

  /**
   * @param Carbon $fecha
   */
  function __construct(
    $fecha,
    $importe,
    $tasaInteres,
    $plazo,
    $periodo,
    $diaPago
  ) {
    $this->fecha = $fecha;
    $this->importe = BigRational::of($importe);
    $this->tasaInteres = BigRational::of($tasaInteres);
    $this->plazo = $plazo;
    $this->periodo = $periodo;
    $this->diaPago = $diaPago;

    $this->numeroCuotas = $this->plazo / $this->periodo;
    $start = $fecha->copy()->addDays(28);
    $this->offset = $start->month + ($start->day > $this->diaPago ? 0 : -1);
    $this->enero = Carbon::createFromDate($start->year, 1, $diaPago)->startOfDay();
  }

  function build(){
    $output = array_fill(0, $this->numeroCuotas, null);
    $saldo = $this->importe;
    
    $recursiveBuild = function ($cuota, $vencimientoCuotaAnterior) use(&$saldo, &$output, &$recursiveBuild){
      if($cuota > $this->numeroCuotas){
        $numerator = BigRational::of(1);
        $denominator = BigRational::of(0);
        yield [$numerator, $denominator];
        // yield new Array(_this.numeroCuotas)
      }
      else{
        $vencimiento = $this->enero->copy()->addMonthsWithoutOverflow($this->periodo*($cuota)+$this->offset - 1, "month");
        $diasTranscurridos = $vencimiento->diffInDays($vencimientoCuotaAnterior);
        $tasa = $this->tasaInteres->multipliedBy($diasTranscurridos)->dividedBy(360);
        $fas = $tasa->plus(1);

        $gen = $recursiveBuild($cuota + 1, $vencimiento);
        [$numerator, $denominator] = $gen->current();

        yield [$numerator->multipliedBy($fas), $denominator->plus($numerator)];
        
        $interes = $saldo->multipliedBy($tasa)->toScale(2, RoundingMode::HALF_UP);
        $saldoMasInteres = $saldo->plus($interes)->toScale(2, RoundingMode::HALF_UP);
        $pagoCuota = $this->pago->isGreaterThan($saldoMasInteres->minus("0.99")) || $cuota == $this->numeroCuotas ? $saldoMasInteres : $this->pago;
        $amortizacion = $pagoCuota->minus($interes);
        $saldo = $saldo->minus($amortizacion);
        $output[$cuota-1] = [
          "numero" => $cuota,
          "vencimiento" => $vencimiento,
          "diasTranscurridos" => $diasTranscurridos,
          "pago" => $pagoCuota,
          "interes" => $interes,
          "amortizacion" => $amortizacion,
          "saldo" => $saldo->toScale(2, RoundingMode::HALF_UP)
        ];

        if(!$saldo->isEqualTo(BigDecimal::zero())) {
            $gen->next();
        }
      }
    };

    $gen = $recursiveBuild(1, $this->fecha);
    [$numerator, $denominator] = $gen->current();
    $this->pago = $this->importe->multipliedBy($numerator->dividedBy($denominator))->toScale(2, RoundingMode::HALF_UP);
    $gen->next();
    return $output;
  }
}