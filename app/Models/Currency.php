<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Currency extends Model
{
    use HasFactory, SaveToUpper;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        "code",
        "name"
    ];

    public function exchangeRates(){
        return $this->hasMany(ExchangeRate::class, "source");
    }

    public function __get($key){
        $value = parent::__get($key);
        // dd(Str::snake("exchangeRates"))

        if(Str::snake($key) === "exchange_rates"){
            $value = $value->mapWithKeys(function ($exchangeRate) {
                return [$exchangeRate->target => $exchangeRate];
            });
        }

        return $value;
    }

    public function toArray()
    {
        return [
            "exchange_rates" => $this->exchangeRates
        ] + parent::toArray();
    }
}
