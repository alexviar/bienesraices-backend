<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Arr;

class ReservaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user, Proyecto $proyecto)
    {
        if($user->can("Ver reservas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Reserva $reserva)
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
        if($user->can("Registrar reservas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
            && (!$user->vendedor_id || Arr::get($payload, "vendedor_id") == $user->vendedor_id)
        ) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Reserva $reserva)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Reserva  $reserva
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Reserva $reserva)
    {
        //
    }
}
