<?php

use App\Http\Controllers\CajaController;
use App\Http\Controllers\CategoriaLoteController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CuotaController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ListaMoraController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ManzanaController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\PagableController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\UFVController;
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

Route::middleware('auth:sanctum')->get('/currencies', [CurrencyController::class, "index"]);

Route::middleware('auth:sanctum')->get('/clientes', [ClienteController::class, "index"]);
Route::middleware('auth:sanctum')->post('/clientes', [ClienteController::class, "store"]);

Route::middleware('auth:sanctum')->get('/vendedores', [VendedorController::class, "index"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/manzanas', [ManzanaController::class, "index"]);
Route::middleware('auth:sanctum')->post('/proyectos/{proyectoId}/manzanas', [ManzanaController::class, "store"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/lotes', [LoteController::class, "index"]);
Route::middleware('auth:sanctum')->post('/proyectos/{proyectoId}/lotes', [LoteController::class, "store"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/ventas', [VentaController::class, "index"]);
Route::middleware('auth:sanctum')->post('/proyectos/{proyectoId}/ventas', [VentaController::class, "store"]);

Route::middleware('auth:sanctum')->get('/proyectos/{proyectoId}/reservas', [ReservaController::class, "index"]);
Route::middleware('auth:sanctum')->post('/proyectos/{proyectoId}/reservas', [ReservaController::class, "store"]);

Route::controller(ProyectoController::class)->prefix('/proyectos')->group(function() {
    Route::prefix('/{proyectoId}')->group(function(){
        Route::middleware('auth:sanctum')->get('/', "show");
        
        Route::controller(CategoriaLoteController::class)->prefix('/categorias')->group(function(){
            Route::middleware('auth:sanctum')->post('/', 'store');
        });
    });
    Route::middleware('auth:sanctum')->get('/', "index");
    Route::middleware('auth:sanctum')->post('/', "store");
});

Route::middleware('auth:sanctum')->get('/transacciones', [CajaController::class, "index"]);
Route::middleware('auth:sanctum')->get('/transacciones/{id}', [CajaController::class, "show"]);
Route::middleware('auth:sanctum')->post('/transacciones', [CajaController::class, "store"]);

Route::middleware('auth:sanctum')->get('lista-mora', [ListaMoraController::class, "index"]);

Route::middleware('auth:sanctum')->get('pagables', [PagableController::class, "index"]);
Route::middleware('auth:sanctum')->get('/pagos/cuotas', [CuotaController::class, "pendientes"]);
Route::middleware('auth:sanctum')->post('/pagos/cuotas/{id}', [CuotaController::class, "pagar"]);

Route::controller(CreditoController::class)->group(function(){
    Route::middleware('auth:sanctum')->get('/creditos/{id}', "show");
    Route::middleware('auth:sanctum')->post('/creditos/{id}/pagos-extras', "store_pago_extra");
});

Route::controller(UFVController::class)->group(function(){
    Route::middleware('auth:sanctum')->get('/ufvs', 'index');
    Route::middleware('auth:sanctum')->post('/ufvs', 'store');
});

