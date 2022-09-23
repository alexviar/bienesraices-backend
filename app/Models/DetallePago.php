<?php

namespace App\Models;

use App\Models\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DetallePago extends Model
{
    use HasFactory;

    protected $table = "detalles_pagos";

    protected $fillable = [
        "moneda",
        "importe",
        "forma_pago",
        "comprobante",
        "numero_comprobante"
    ];

    protected $appends = [
        "forma_pago_text"
    ];

    function getFormaPagoTextAttribute(){
        switch($this->forma_pago){
            case 1:
                return "Efectivo";
            case 2:
                return "Deposito o transferencia bancaria";
            case 3:
                return "Excedentes de pago de clientes";
            default:
                return "Error";
        }
    }

    function getImporteAttribute($value)
    {
        return new Money($value, $this->moneda);
    }
    
    function getComprobanteAttribute($value)
    {
        return $value ? route("comprobantes", [
            "comprobante" => Str::after($value, "/")
        ]) : $value;
        // return $value ? Storage::url($value) : $value;
    }
}
