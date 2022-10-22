<?php

namespace App\Providers;

use App\Models\Cuota;
use App\Models\PagoExtra;
use App\Models\Permission;
use App\Models\User;
use App\Policies\CuotaPolicy;
use App\Policies\PagoExtraPolicy;
use App\Policies\UserPolicy;
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
        User::class => UserPolicy::class,
        PagoExtra::class => PagoExtraPolicy::class,
        Cuota::class => CuotaPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::before(function (User $user, $ability, $argument) {
            if($user->estado !== 1 || !$user->hasVerifiedEmail()){
                return false;
            }

            $permissions = $user->getAllPermissions();
            // do{
                if($permissions->contains(function(Permission $value) use($ability){
                    return $value->checkPermissionTo($ability);
                })){
                    return true;
                }
            //     $permissions = $permissions->pluck("permissions")->flatten();
            // } while(!$permissions->isEmpty());
        });

        Gate::after(function ($user) {
            if($user->isSuperUser()){
                return true;
            }
        });
    }
}
