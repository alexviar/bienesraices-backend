<?php

namespace App\Models\ValueObjects;

use App\Models\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

/**
 * @property BigDecimal $amount
 * @property Currency $currency
 */
class Money implements Arrayable {
    const BUY = 1;
    const SELL = 2;

    /** @var Money|null $originalValue */
    private $originalValue;
    /** @var BigDecimal|null $_amount */
    private $_amount;
    /** @var Currency $_currency */
    private $_currency;

    /**
     * @param BigDecimal|string $amount
     * @param Currency $currency
     */
    function __construct($amount, $currency){
        $this->_amount = BigDecimal::of($amount);
        $this->_currency = $currency;
        $this->originalValue = null;
    }

    function __get($attribute) {
        if($attribute == "amount") return $this->_amount;
        else if($attribute == "currency") return $this->_currency;
    }

    function __call($name, $arguments)
    {
        if($name === "add"){
            return $this->plus(...$arguments);
        }
        else if($name === "sub"){
            return $this->minus(...$arguments);
        }
    }

    /**
     * @return Money
     */
    function plus($money){
        if($this->currency->code != $money->currency->code) throw new Exception("Currencies should to be the same but gets {$this->currency->code} and {$money->currency->code}");

        return new Money($this->_amount->plus($money->amount), $this->currency);
    }

    /**
     * @return Money
     */
    function minus($money){
        if($this->currency->code != $money->currency->code) throw new Exception("Currencies should to be the same but gets {$this->currency->code} and {$money->currency->code}");

        return new Money($this->_amount->minus($money->amount), $this->currency);
    }

    /**
     * @param BigDecimal|string $value
     * @return Money
     */
    function multipliedBy($value){
        $amount = $this->_amount->multipliedBy($value);
        return new Money($amount, $this->_currency);
    }

    /**
     * @param BigDecimal|string $value
     * @return Money
     */
    function dividedBy($value){
        $amount = $this->_amount->dividedBy($value, 10, RoundingMode::HALF_UP);
        return new Money($amount, $this->_currency);
    }

    /**
     * @return Money
     */
    function round($scale=2, $roundingMode = RoundingMode::HALF_UP){
        return new Money($this->_amount->toScale($scale, $roundingMode), $this->_currency);
    }

    /**
     * @param Currency|string $currency
     * @param 1|2 $exchangeMode
     */
    function exchangeTo($currency, $options = []){

        $exchangeMode = Arr::get($options, "exchangeMode", Money::SELL);
        $preserveOriginal = Arr::get($options, "preserveOriginal", true);

        if(is_string($currency)) $currency = Currency::find($currency);
        if(!$currency) throw new Exception("Unknown currency");

        if($this->originalValue) return $this->originalValue->exchangeTo($currency, $exchangeMode);

        if($this->_currency->code === $currency->code) return clone $this;
        if($exchangeRate = Arr::get($this->currency->exchangeRates, $currency->code)){
            $result = $this->multipliedBy($exchangeMode == Money::SELL ? $exchangeRate->sell_rate : $exchangeRate->buy_rate);
        }
        else if($exchangeRate = Arr::get($currency->exchangeRates, $this->currency->code)){
            $result = $this->dividedBy($exchangeMode == Money::BUY ? $exchangeRate->sell_rate : $exchangeRate->buy_rate);
        }
        else{
            throw new Exception("No se encontraron valores para el cambio de divisas: '{$this->currency->code}' -> '{$currency->code}'");
        }
        
        if($preserveOriginal) $result->originalValue = $this;

        return $result;
    }

    function toArray(){
        return [
            "amount" => (string) $this->_amount,
            "currency" => $this->currency->code
        ];
    }
}