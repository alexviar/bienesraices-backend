<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProyectoPolicy
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
        if($user->can("Ver proyectos")) return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Proyecto  $proyecto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Proyecto $proyecto)
    {
        if($user->can("Ver proyectos")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        if($user->can("Registrar proyectos")) return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Proyecto  $proyecto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Proyecto $proyecto)
    {
        if($user->can("Editar proyectos")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($proyecto))
        ) return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Proyecto  $proyecto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, Proyecto $proyecto)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Proyecto  $proyecto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, Proyecto $proyecto)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Proyecto  $proyecto
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, Proyecto $proyecto)
    {
        //
    }
}
