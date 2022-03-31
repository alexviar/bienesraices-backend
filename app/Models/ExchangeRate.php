<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "source",
        "target",
        "buy_rate",
        "sell_rate"
    ];
}
