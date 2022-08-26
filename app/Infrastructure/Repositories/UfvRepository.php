<?php

namespace App\Infrastructure\Repositories;

use App\Models\Interfaces\UfvRepositoryInterface;
use DateTimeInterface;
use App\Models\UFV;
use Brick\Math\BigDecimal;

class UfvRepository implements UfvRepositoryInterface {
    /**
     * @return BigDecimal|null
     */
    function findByDate(DateTimeInterface $fecha)
    {
        // $ufv = UFV::firstWhere("fecha", $fecha);
        // return $ufv ? BigDecimal::of($ufv->valor) : null;
        return BigDecimal::one();
    }
}