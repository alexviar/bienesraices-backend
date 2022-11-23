<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
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
            $query->where(function($query) use($date){
                $query->whereNull("start")
                    ->orWhere("start", "<=", $date);
            })->where(function($query) use($date){
                $query->whereNull("end")
                    ->orWhere("end", ">=", $date);
            });
        }
    }

    function index(Request $request)
    {
        $query = ExchangeRate::query();
        return $this->buildResponse($query, $request->all());
    }
}
