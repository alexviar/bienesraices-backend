<?php

namespace App\Policies;

use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TransaccionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, $queryArgs)
    {
        if($user->can("Ver transacciones")) return true;
    }

    public function viewPagables(User $user, $queryArgs)
    {
        if($user->can("Registrar transacciones")) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Transaccion $transaccion)
    {
        if($user->can("Ver transacciones")) return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, $payload)
    {
        if($user->can("Registrar transacciones")) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Transaccion $transaccion)
    {
        //
    }

    /**
     * Determina si el usuario puede anular .
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function cancel(User $user, Transaccion $transaccion)
    {
        if($transaccion->estado == 2) return false;
        if($user->can("Anular transacciones")) return true;
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Transaccion $transaccion)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Transaccion  $transaccion
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Transaccion $transaccion)
    {
        //
    }
}
