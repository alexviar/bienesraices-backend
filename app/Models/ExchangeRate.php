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

    protected $attributes = [
        "indirect" => false
    ];

    protected $fillable = [
        "valid_from",
        // "end",
        "source",
        "target",
        "rate",
        "indirect"
    ];

    protected $cast = [
        "valid_from" => "date:Y-m-d",
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

    static function findByDate($source, $target, $date)
    {
        return static::whereSource($source)
            ->whereTarget($target)
            ->where("valid_from", "<=", $date)
            ->latest("valid_from")
            ->first();
    }
}
