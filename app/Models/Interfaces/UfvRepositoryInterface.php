<?php

namespace App\Models\Interfaces;

use Brick\Math\BigDecimal;
use DateTimeInterface;

interface UfvRepositoryInterface {

    /**
     * @return BigDecimal|null
     */
    function findByDate(DateTimeInterface $fecha);
}