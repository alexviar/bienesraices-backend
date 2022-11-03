<?php

namespace App\Policies;

use App\Models\Credito;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CreditoPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Credito $credito)
    {
        if($user->can("Ver ventas")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($credito->creditable->proyecto))
            && (!$user->vendedor_id || $user->vendedor_id == $credito->creditable->vendedor_id)
        ) return true;
    }

    public function printHistorialPagos(User $user, Credito $credito)
    {
        if($user->can("Imprimir historial de pagos")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($credito->creditable->proyecto))
            && (!$user->vendedor_id || $user->vendedor_id == $credito->creditable->vendedor_id)
        ) return true;
    }

    public function printPlanPagos(User $user, Credito $credito)
    {
        if($user->can("Imprimir plan de pagos")
            && ($user->proyectos->isEmpty() || $user->proyectos->contains($credito->creditable->proyecto))
            && (!$user->vendedor_id || $user->vendedor_id == $credito->creditable->vendedor_id)
        ) return true;
    }
}
