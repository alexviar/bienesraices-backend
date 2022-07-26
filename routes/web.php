<?php

use App\Http\Controllers\AuthController;
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

Route::get('/seed', function(){
  Artisan::call("migrate:fresh");
  Artisan::call("db:seed InitialLoadSeeder");
});

Route::post('/login', [AuthController::class, 'login'])->name("login");
Route::post('/logout', [AuthController::class, 'logout']);

Route::fallback(function () {
  return File::get(public_path() . "/build/index.html");
});
