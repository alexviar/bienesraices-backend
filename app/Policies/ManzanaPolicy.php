<?php

namespace App\Policies;

use App\Models\Manzana;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Log;

class ManzanaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Proyecto $proyecto, $queryArgs)
    {
        if($user->can("Ver manzanas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manzana  $manzana
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Manzana $manzana)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Proyecto $proyecto, $payload)
    {
        $plano = $proyecto->plano;
        if(/*!$plano->is_vigente || */$plano->is_locked) return false;
        if($user->can("Registrar manzanas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manzana  $manzana
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Manzana $manzana)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manzana  $manzana
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Manzana $manzana)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manzana  $manzana
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Manzana $manzana)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Manzana  $manzana
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Manzana $manzana)
    {
        //
    }
}
