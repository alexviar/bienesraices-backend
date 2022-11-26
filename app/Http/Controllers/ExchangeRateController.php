<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ExchangeRateController extends Controller
{

    protected function applyFilters($query, $queryArgs)
    {
        if($from = Arr::get($queryArgs, "filter.source")){
            $query->whereSource($from);
        }
        if($to = Arr::get($queryArgs, "filter.target")){
            $query->whereTarget($to);
        }
        if($date = Arr::get($queryArgs, "filter.date")){
            $query->where("valid_from", "<=", $date)->latest("valid_from");
        }
    }

    function index(Request $request)
    {
        $this->authorize("viewAny", [ExchangeRate::class, $request->all()]);
        $query = ExchangeRate::query()->latest();
        return $this->buildResponse($query, $request->all());
    }

    function store(Request $request)
    {
        $this->authorize("create", [ExchangeRate::class, $request->all()]);
        $payload = $request->validate([
            "valid_from" => "required|date",
            // "end" => "nullable|date",
            "source" => "required|exists:currencies,code",
            "target" => "required|exists:currencies,code|different:source",
            "rate" => "required|numeric",
            "indirect" => "nullable|boolean"
        ], [
            "target.different" => "La :attribute y la :other deben ser diferentes."
        ], [
            "valid_from" => "válido desde",
            "source" => "moneda de origen",
            "target" => "moneda de destino",
            "rate" => "cotización"
        ]);

        return ExchangeRate::create($payload);
    }

    function update(Request $request, $id)
    {
        $exchangeRate = $this->findExchangeRate($id);
        $this->authorize("update", [$exchangeRate, $request->all()]);
        $payload = $request->validate([
            "valid_from" => "required|date",
            // "end" => "nullable|date",
            "source" => "required|exists:currencies,code",
            "target" => "required|exists:currencies,code|different:source",
            "rate" => "required|numeric",
            "indirect" => "nullable|boolean"
        ], [
            "target.different" => "La :attribute y la :other deben ser diferentes."
        ], [
            "valid_from" => "válido desde",
            "source" => "moneda de origen",
            "target" => "moneda de destino",
            "rate" => "cotización"
        ]);

        $exchangeRate->update($payload);
        return $exchangeRate;
    }

    function delete(Request $request, $id){
        $exchangeRate = $this->findExchangeRate($id);
        $this->authorize("forceDelete", [$exchangeRate]);

        $exchangeRate->delete();
    }

    private function findExchangeRate($id)
    {
        $exchangeRate = ExchangeRate::find($id);
        if(!$exchangeRate){
            throw new ModelNotFoundException("Registro no encontrado.");
        }
        return $exchangeRate;
    }
}
