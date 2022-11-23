<?php

namespace App\Policies;

use App\Models\ExchangeRate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExchangeRatePolicy
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
        if($user->can("Ver tipos de cambio")) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExchangeRate  $exchangeRate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if($user->can("Registrar tipos de cambio")) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExchangeRate  $exchangeRate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, ExchangeRate $exchangeRate)
    {
        if($user->can("Editar tipos de cambio")) return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExchangeRate  $exchangeRate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExchangeRate  $exchangeRate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, ExchangeRate $exchangeRate)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ExchangeRate  $exchangeRate
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, ExchangeRate $exchangeRate)
    {
        //
    }
}
