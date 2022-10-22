<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
