<?php

namespace App\Infrastructure\Providers;

use App\Models\ExchangeRate;
use App\Models\Interfaces\CurrencyExchangeProvider;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;

class DatabaseCurrencyExchangeProvider implements CurrencyExchangeProvider
{
    public function exchange($from, $to, $amount, $date=null, $direct = true)
    {
        if(!$date) $date = Carbon::today();
        if(!$direct) [$from, $to] = [$to, $from];

        // $query = ExchangeRate::whereSource($from)->whereTarget($to);
        // $query->where(function($query) use($date){
        //     $query->whereNull("start")
        //         ->orWhere("start", "<=", $date);
        // })->where(function($query) use($date){
        //     $query->whereNull("end")
        //         ->orWhere("end", ">=", $date);
        // });


        /** @var ExchangeRate $exchangeRate */
        $exchangeRate = ExchangeRate::findByDate($from, $to, $date);
        if(!$exchangeRate) abort(500, "Currency exchange rate does not found ($from, $to, {$date->format("Y-m-d")}, $direct).");
        $rate = $direct ?  $exchangeRate->rational_rate : $exchangeRate->rational_rate->reciprocal();
        return $rate->multipliedBy($amount)->toScale(20, RoundingMode::HALF_UP);
    }
}
