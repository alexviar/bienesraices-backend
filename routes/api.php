<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuentasPorCobrarController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ManzanaController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\VentaController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/currencies', [CurrencyController::class, "index"]);

Route::get('/clientes', [ClienteController::class, "index"]);
Route::post('/clientes', [ClienteController::class, "store"]);

Route::get('/vendedores', [VendedorController::class, "index"]);

Route::get('/proyectos/{proyectoId}/manzanas', [ManzanaController::class, "index"]);

Route::get('/proyectos/{proyectoId}/lotes', [LoteController::class, "index"]);

Route::get('/proyectos/{proyectoId}/ventas', [VentaController::class, "index"]);
Route::post('/proyectos/{proyectoId}/ventas', [VentaController::class, "store"]);

Route::get('/proyectos/{proyectoId}/reservas', [ReservaController::class, "index"]);
Route::post('/proyectos/{proyectoId}/reservas', [ReservaController::class, "store"]);

Route::get('/proyectos/{proyectoId}', [ProyectoController::class, "show"]);
Route::get('/proyectos', [ProyectoController::class, "index"]);

Route::get('/cuentas-por-cobrar', [CuentasPorCobrarController::class, "index"]);
Route::get('transacciones', [CajaController::class, "index"]);
Route::post('transacciones', [CajaController::class, "store"]);
