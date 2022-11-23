<?php

namespace App\Models;

use App\Models\Traits\SaveToUpper;
use Brick\Math\BigRational;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 * @property BigRational $rate
 */
class ExchangeRate extends Model
{
    use HasFactory, SaveToUpper;

    protected $fillable = [
        "source",
        "target",
        "rate",
        "indirect"
    ];

    #region Accessors
    /**
     * 
     */
    public function getRationalRateAttribute()
    {
        $rational = BigRational::one();
        if ($this->indirect) {
            return $rational->dividedBy($this->rate);
        }
        else{
            return $rational->multipliedBy($this->rate);
        }
    }
    #endregion
}
