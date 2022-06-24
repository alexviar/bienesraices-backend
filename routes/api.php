<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuentasPorCobrarController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ListaMoraController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ManzanaController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\VentaController;
use App\Models\Currency;
use App\Models\ValueObjects\Money;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/template', function(){
    $image = public_path("logo192.png");
    $mime = getimagesize($image)["mime"];
    $data = file_get_contents($image);
    $dataUri = 'data:image/' . $mime . ';base64,' . base64_encode($data);

    $venta = Venta::find(1);

    // return view("pdf.plan_pagos", [
    //     "img" => $dataUri,
    // "id" => "1",
    // "fecha" => "dd/mm/yyyy",
    // "proyecto" => ["nombre" => "oportunidad IV"],
    // "manzana" => ["numero" => 10],
    // "lote" => ["numero" => 10, "manzana" => ["numero" => 10]],
    // "cliente" => [ "nombre" => "Lorem Ipsum", "codigo_pago" => "CLI187"],
    // "moneda" => "USD",
    // "precio" => new Money("10000", Currency::find("USD")),
    // "cuota_inicial" => new Money("500", Currency::find("USD")),
    // "interes" => "10%",
    // ]);

    return Barryvdh\DomPDF\Facade\PDF::loadView("pdf.plan_pagos", [
        "img" => $dataUri,
        "venta" => $venta
    ])->setPaper([0, 0, 72*8.5, 72*13])->stream();
});

Route::get('/seed', function(){
    Artisan::call("migrate:fresh");
    Artisan::call("db:seed InitialLoadSeeder");
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->get('/currencies', [CurrencyController::class, "index"]);

Route::middleware('auth:sanctum')->get('/clientes', [ClienteController::class, "index"]);
Route::middleware('auth:sanctum')->post('/clientes', [ClienteController::class, "store"]);

Route::middleware('auth:sanctum')->get('/vendedores', [VendedorController::class, "index"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/manzanas', [ManzanaController::class, "index"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/lotes', [LoteController::class, "index"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/ventas/{id}/plan_pagos', [VentaController::class, "print_plan_pagos"])->name("ventas.plan_pago");
Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/ventas', [VentaController::class, "index"]);
Route::middleware('auth:sanctum')->post('/proyectos/{proyectoId}/ventas', [VentaController::class, "store"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/reservas', [ReservaController::class, "index"]);
Route::middleware('auth:sanctum')->post('/proyectos/{proyectoId}/reservas', [ReservaController::class, "store"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}', [ProyectoController::class, "show"]);
Route::middleware('auth:sanctum')->get('/proyectos', [ProyectoController::class, "index"]);

Route::middleware('auth:sanctum')->get('/cuentas-por-cobrar', [CuentasPorCobrarController::class, "index"]);
Route::middleware('auth:sanctum')->get('/transacciones', [CajaController::class, "index"]);
Route::middleware('auth:sanctum')->post('/transacciones', [CajaController::class, "store"]);

Route::middleware('auth:sanctum')->get('lista-mora', [ListaMoraController::class, "index"]);
