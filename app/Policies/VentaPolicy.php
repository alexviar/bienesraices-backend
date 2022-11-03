<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\Reserva;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Arr;

class VentaPolicy
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
        if($user->can("Ver ventas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Venta $venta)
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
        if($user->can("Registrar ventas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
            && (!$user->vendedor_id || Arr::get($payload, "vendedor_id") == $user->vendedor_id)
        ) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Venta $venta)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Venta $venta)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Venta $venta)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Venta  $venta
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Venta $venta)
    {
        //
    }

    public function printNotaVenta(User $user, Venta $venta)
    {
        if($user->can("Imprimir comprobantes de venta")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($venta->proyecto))
            && (!$user->vendedor_id || $venta->vendedor_id == $user->vendedor_id)
        ) return true;
    }
}
