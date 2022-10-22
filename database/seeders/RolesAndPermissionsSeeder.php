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
        User::find(1)->syncRoles([$superUsers]);

        $tag = "Usuarios";
        $verUsuarios = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Ver usuarios",
            "guard_name" => "sanctum"
        ]);
        $registrarUsuarios = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Registrar usuarios",
            "guard_name" => "sanctum"
        ]);
        $editarUsuarios = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Editar usuarios",
            "guard_name" => "sanctum"
        ]);
        $bloquearUsuarios = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Bloquear usuarios",
            "guard_name" => "sanctum"
        ]);
        $desbloquearUsuarios = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Desbloquear usuarios",
            "guard_name" => "sanctum"
        ]);

        $tag = "Roles";
        $verRoles = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Ver roles",
            "guard_name" => "sanctum"
        ]);
        $registrarRoles = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Registrar roles",
            "guard_name" => "sanctum"
        ]);
        $editarRoles = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Editar roles",
            "guard_name" => "sanctum"
        ]);
        $eliminarRoles = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Eliminar roles",
            "guard_name" => "sanctum"
        ]);
        
        $tag = "Vendedores";
        $verVendedores = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Ver vendedores",
            "guard_name" => "sanctum"
        ]);
        $registrarVendedores = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Registrar vendedores",
            "guard_name" => "sanctum"
        ]);
        $editarVendedores = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Editar vendedores",
            "guard_name" => "sanctum"
        ]);
        $eliminarVendedores = Permission::firstOrCreate([
            "tag" => $tag,
            "name" =>  "Eliminar vendedores",
            "guard_name" => "sanctum"
        ]);

        $registrarUsuarios->syncPermissions([
            $verVendedores
        ]);
        $editarUsuarios->syncPermissions([
            $verVendedores
        ]);
    }
}
