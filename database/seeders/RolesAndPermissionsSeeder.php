<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $superUsers = Role::firstOrCreate([
            "name" => "Super usuarios",
            "guard_name" => "sanctum"
        ]);
        $admin = User::find(1);
        $admin->syncRoles([$superUsers]);
        $admin->update([
            "email_verified_at" => now()
        ]);
        
        $permissionsConfig = config("permission.permissions");
        foreach($permissionsConfig["groups"] as $group=>$permissions){
            foreach($permissions as $permission){
                Permission::firstOrCreate([
                    "tag" => $group,
                    "name" =>  $permission,
                    "guard_name" => "sanctum"
                ]);
            }
        }
        foreach($permissionsConfig["includes"] as $permission=>$includes){
            /** @var Permission model */
            $model = Permission::findByName($permission, "sanctum");
            $model->syncPermissions($includes);
        }
    }
}
