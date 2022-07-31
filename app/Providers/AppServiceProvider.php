<?php

namespace App\Providers;

use App\Http\Reports\Venta\HistorialPagos;
use App\Infrastructure\Repositories\UfvRepository;
use App\Models\Interfaces\UfvRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->instance(HistorialPagos::class, new HistorialPagos());
        $this->app->bind(UfvRepositoryInterface::class, UfvRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
