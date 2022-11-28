<?php

use App\Console\Commands\ReleaseLotes;
use App\Models\Lote;
use App\Models\Reserva;
use Illuminate\Support\Carbon;
use Tests\TestCase;

it("libera los lotes de reservas vencidas", function(){
    /** @var TestCase $this */
    $reservas = Reserva::factory(5, [
        "vencimiento" => Carbon::today()
    ])->sequence(
        ["estado"=>1],
        ["estado"=>1],
        ["estado"=>2],
        ["estado"=>3],
        ["estado"=>4],
    )->create();
    $cmd = new ReleaseLotes();
    $cmd->handle();
    expect($reservas->pluck("lote")->every(fn ($lote)=>$lote->estado == 3))->toBeTrue();
    $this->travel(1)->day();
    $cmd = new ReleaseLotes();
    $cmd->handle();
    expect($reservas[0]->fresh()->estado)->toBe(4);
    expect($reservas[0]->fresh()->lote->estado)->toBe(1);
    expect($reservas[1]->fresh()->estado)->toBe(4);
    expect($reservas[1]->fresh()->lote->estado)->toBe(1);
    expect($reservas[2]->fresh()->estado)->toBe(2);
    expect($reservas[2]->fresh()->lote->estado)->toBe(3);
    expect($reservas[3]->fresh()->estado)->toBe(3);
    expect($reservas[3]->fresh()->lote->estado)->toBe(3);
    expect($reservas[4]->fresh()->estado)->toBe(4);
    expect($reservas[4]->fresh()->lote->estado)->toBe(3);
})/*->with([
    function(){
        return [
            "reserva" => Reserva::factory([
                "vencimiento" => Carbon::today(),
                "estado" => 1
            ]),
            ""
        ]
    }
])*/;