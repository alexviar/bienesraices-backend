<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Assert;

uses(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toMatchNestedArray', function ($array, $path="") {
    if (is_object($this->value) && method_exists($this->value, 'toArray')) {
        $valueAsArray = $this->value->toArray();
    } else {
        $valueAsArray = (array) $this->value;
    }

    foreach ($array as $key => $value) {
        Assert::assertArrayHasKey($key, $valueAsArray);

        $qulifiedKey = $path !== "" ? $path.".".$key : $key;
        if(is_array($value)){
            $savedValue = $this->value;
            $this->value = $valueAsArray[$key];
            $this->toMatchNestedArray($value, $qulifiedKey);
            $this->value = $savedValue;
        }
        else{
            Assert::assertEquals(
                $value,
                $valueAsArray[$key],
                sprintf(
                    'Failed asserting that an array has a key %s with the value %s.',
                    $this->export($qulifiedKey),
                    $this->export($valueAsArray[$key]),
                ),
            );
        }
    }

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

uses()
->beforeEach(function(){
    $this->faker->seed(2022);  
    $this->seed();
})->in("Feature");