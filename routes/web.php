<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\VentaController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/migrate', function(){
  Artisan::call("migrate");
});

// Route::get('/seed', function(){
//   Artisan::call("migrate:fresh");
//   Artisan::call("db:seed InitialLoadSeeder");
// });

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
  $request->fulfill();

  return response()->json();
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::controller(AuthController::class)->group(function(){
  Route::post('/login', 'login')->name("login");
  Route::post('/logout', 'logout');
  Route::middleware("auth:sanctum")->post('/change-password', 'change_password');
  Route::middleware('guest')->post('/forgot-password', 'forgot_password');
  Route::middleware('guest')->post('/reset-password', 'reset_password');
});

Route::middleware("auth:sanctum")->get("/comprobantes/{comprobante}", [CajaController::class, "comprobante"])->name("comprobantes");

Route::controller(CreditoController::class)->group(function(){
  Route::middleware("auth:sanctum")->get('/creditos/{id}/historial_pagos', "print_historial_pagos")->name("creditos.historial_pagos");
  Route::middleware("auth:sanctum")->get('/creditos/{id}/plan_pagos', "print_plan_pagos")->name("creditos.plan_pago");
});

Route::controller(VentaController::class)->group(function(){
  Route::middleware("auth:sanctum")->get('/proyectos/{proyectoId}/ventas/{ventaId}/nota-venta', "print_nota_venta")->name("ventas.nota_venta");
});

Route::fallback(function (Request $request) {
  if ($request->expectsJson()) {
      return abort(404);
  }
  return File::get(public_path() . "/build/index.html");
});
