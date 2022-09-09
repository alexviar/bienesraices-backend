<?php

namespace App\Policies;

use App\Models\Cuota;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CuotaPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function pagar(User $user, Cuota $cuota, $payload)
    {    
        if(!$cuota->pendiente) return Response::deny("Solo puede pagar cuotas pendientes.");
    }
}
