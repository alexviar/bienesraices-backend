<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function create(User $login, $payload)
    {
        if($login->can("Registrar usuarios")) return true;
    }
}
