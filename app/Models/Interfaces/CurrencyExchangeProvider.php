<?php

namespace App\Models\Interfaces;

interface CurrencyExchangeProvider
{
    public function exchange($from, $to, $amount, $date = null, $direct = true);
}
