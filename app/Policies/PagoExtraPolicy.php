<?php

namespace App\Policies;

use App\Models\Credito;
use App\Models\PagoExtra;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class PagoExtraPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PagoExtra  $pagoExtra
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, PagoExtra $pagoExtra)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user, Credito $credito, $payload)
    {
        if($credito->estado != 1) return Response::deny("El credito ha sido anulado.");
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PagoExtra  $pagoExtra
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, PagoExtra $pagoExtra)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PagoExtra  $pagoExtra
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, PagoExtra $pagoExtra)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PagoExtra  $pagoExtra
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, PagoExtra $pagoExtra)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\PagoExtra  $pagoExtra
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, PagoExtra $pagoExtra)
    {
        //
    }
}
