<?php

use App\Events\ReservaCreated;
use App\Models\Reserva;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\Event;

it('registra una nueva reserva', function ($dataset) {

    $data = $dataset["data"];
    $proyectoId = $data["proyecto_id"];
    
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/reservas", $data);
    
    $response->assertCreated();
    $reserva = Reserva::find($response->json("id"));
    expect($reserva->getAttributes())->toMatchArray([
        "importe" => (string) BigDecimal::of($data["importe"])->toScale(4, RoundingMode::HALF_UP),
        "saldo" => (string) BigDecimal::of($data["importe"])->toScale(4, RoundingMode::HALF_UP),
        "saldo_contado" => (string) BigDecimal::of($data["saldo_contado"])->toScale(4, RoundingMode::HALF_UP),
        "saldo_credito" => (string) BigDecimal::of($data["saldo_credito"])->toScale(4, RoundingMode::HALF_UP),
    ] + $data);
})->with([
    function(){
        $data = Reserva::factory()->raw();
        return [
            "data" => $data,
        ];
    }
]);

it('despacha el evento de nueva reserva creada', function ($dataset) {

    $data = $dataset["data"];
    $proyectoId = $data["proyecto_id"];

    Event::fake();
    
    $response = $this->actingAs(User::find(1))->postJson("/api/proyectos/$proyectoId/reservas", $data);
    
    $response->assertCreated();
    Event::assertDispatched(ReservaCreated::class, function(ReservaCreated $event) use($response){
        $this->assertSame($response->json("id"), $event->reserva->id);
        return true;
    });
    
})->with([
    function(){
        $data = Reserva::factory()->raw();
        return [
            "data" => $data,
        ];
    }
]);
