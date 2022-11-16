<?php

namespace App\Providers;

use App\Http\Reports\Venta\HistorialPagos;
use App\Infrastructure\Providers\DatabaseCurrencyExchangeProvider;
use App\Infrastructure\Repositories\UfvRepository;
use App\Models\Interfaces\UfvRepositoryInterface;
use App\Models\Services\DisminucionPlazo;
use App\Models\Services\Diferido;
use App\Models\Services\ProgramadorPagoExtra;
use App\Models\Services\Prorrateo;
use App\Models\Services\SoloInteres;
use App\Models\ValueObjects\Money;
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
        $this->app->instance(ProgramadorPagoExtra::class, new Prorrateo(
            new DisminucionPlazo(
                new SoloInteres(
                    new Diferido()
                )
            )
        ));
        $this->app->bind(UfvRepositoryInterface::class, UfvRepository::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Money::setCurrencyExchangeProvider(new DatabaseCurrencyExchangeProvider);
    }
}
