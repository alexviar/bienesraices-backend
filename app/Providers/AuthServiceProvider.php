<?php

namespace App\Providers;

use App\Models\PagoExtra;
use App\Policies\PagoExtraPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
        PagoExtra::class => PagoExtraPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            if($user->estado !== 1){
                return false;
            }
        });

        Gate::after(function ($user) {
            if($user->isSuperUser()){
                return true;
            }
        });
    }
}
