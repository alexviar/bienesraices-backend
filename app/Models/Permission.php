<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Contracts\Permission as ContractsPermission;
use Spatie\Permission\Traits\HasPermissions;

/**
 * @method static function firstOrCreate(array $attributes): Permission
 */
class Permission extends \Spatie\Permission\Models\Permission
{
    use HasFactory, HasPermissions;

    function getIncludesAttribute(){
        $this->getPermissionNames();
    }

    function hasPermissionViaRole(ContractsPermission $permission): bool
    {
        return false;
    }
}
