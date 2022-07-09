<?php

namespace Tests\Unit;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $a = BigDecimal::of("0.1")->multipliedBy(30)->toBigRational()->dividedBy(360);
        $b = BigRational::of("10351.8");
        $this->assertTrue($b->multipliedBy($a)->isEqualTo("86.265"));
    }
}
