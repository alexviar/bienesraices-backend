<?php

namespace App\Policies;

use App\Models\Lote;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LotePolicy
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
        if($user->can("Ver lotes")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lote  $lote
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Lote $lote)
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
        if($user->can("Registrar lotes")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lote  $lote
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Lote $lote)
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lote  $lote
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Lote $lote)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lote  $lote
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Lote $lote)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Lote  $lote
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Lote $lote)
    {
        //
    }
}
