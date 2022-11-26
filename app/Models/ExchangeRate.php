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
        } else {
            return $rational->multipliedBy($this->rate);
        }
    }
    #endregion

    #region Relationships
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sourceCurrency()
    {
        return $this->belongsTo(Currency::class, "source", "code");
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function targetCurrency()
    {
        return $this->belongsTo(Currency::class, "target", "code");
    }
    #endregion

    public function toArray()
    {
        return [
            "source" => $this->sourceCurrency,
            "target" => $this->targetCurrency,
        ] + parent::toArray();
    }

    static function findByDate($source, $target, $date)
    {
        return static::whereSource($source)
            ->whereTarget($target)
            ->where("valid_from", "<=", $date)
            ->latest("valid_from")
            ->first();
    }
}
