<?php

use App\Infrastructure\Providers\DatabaseCurrencyExchangeProvider;
use App\Models\ValueObjects\Money;

test('conversion', function ($from, $to, $amount, $direct, $result) {
    try{
        Money::setCurrencyExchangeProvider(new DatabaseCurrencyExchangeProvider());
        $money = new Money($amount, $from);
        $money = $money->exchangeTo($to, [
            "exchangeMode" => $direct ? Money::SELL : Money::BUY
        ]);
    }
    catch(Throwable $t){
        dd($t);
    }
    expect((string) $money->stripTrailingZeros())->toBe($result);
})->with([
    ["USD", "BOB", null, true, "- BOB"],
    ["USD", "BOB", "1", true, "6.96 BOB"],
    ["USD", "BOB", "1", false, "6.86 BOB"],
    ["BOB", "USD", "1", true, "0.14577259475218658892 USD"],
    ["BOB", "USD", "1", false, "0.14367816091954022989 USD"],
]);
