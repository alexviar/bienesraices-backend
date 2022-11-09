<?php

use App\Console\Commands\ReleaseLotes;
use App\Models\Lote;
use App\Models\Reserva;
use Illuminate\Support\Carbon;
use Tests\TestCase;

it("libera los lotes de reservas vencidas", function(){
    /** @var TestCase $this */
    $reservas = Reserva::factory(10, [
        "vencimiento" => Carbon::today()
    ])->create();
    $cmd = new ReleaseLotes();
    $cmd->handle();
    expect($reservas->pluck("lote")->every(fn ($lote)=>$lote->estado == 3))->toBeTrue();
    $this->travel(1)->day();
    $cmd = new ReleaseLotes();
    $cmd->handle();
    expect($reservas->pluck("lote")->every(fn ($lote)=>$lote->fresh()->estado == 1))->toBeTrue();
});