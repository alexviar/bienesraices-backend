<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Arr;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $login, $queryArgs){
        if($login->can("Ver usuarios")) return true;
    }

    public function create(User $login, $payload)
    {
        if($login->can("Registrar usuarios") && (
            // $login->isSuperUser() || 
            !collect(Arr::get($payload, "roles", []))->contains("Super usuarios")
        )) return true;
    }

    public function update(User $login, User $user, $payload)
    {
        if($login->can("Editar usuarios")) return true;
    }

    public function changeStatus(User $login, $user, $action){
        if($login->can("Activar/Desactivar usuarios")) return true;
    }
}
