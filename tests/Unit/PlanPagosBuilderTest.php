<?php

use App\Models\PlanPagosBuilder;
use Brick\Math\BigDecimal;
use Illuminate\Support\Carbon;

it("Genera un plan de pagos", function(){
    $builder = new PlanPagosBuilder(
        Carbon::createFromFormat("Y-m-d", "2022-02-28")->startOfDay(),
        BigDecimal::of("10030.96"),
        BigDecimal::of("0.1"),
        48,
        1,
        31
    );
    // dd(json_encode($builder->build()));
});