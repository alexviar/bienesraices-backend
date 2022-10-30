<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize("viewAny", [Role::class, $request->all()]);
        $queryArgs = $request->only(["search", "filter", "page"]);
        return $this->buildResponse(Role::query(), $queryArgs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize("create", [Role::class, $request->all()]);
        $payload = $request->validate([
            "name" => "required|string",
            "description" => "nullable|string",
            "permissions" => "required|array",
            "permissions.*" => "exists:permissions,name"
        ], [
            "permissions.required" => "Debe asignar al menos un permiso."
        ]);

        /** @var Role $rol */
        $rol = Role::create(Arr::except($payload, ["permissions"]));
        $rol->givePermissionTo($payload["permissions"]);
        return $rol;
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $rolId)
    {
        $rol = $this->findRol($rolId);
        $this->authorize("view", [$rol]);
        $rol->loadMissing("permissions");
        return $rol;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        //
    }

    private function findRol($rolId){
        $rol = Role::find($rolId);
        if(!$rol){
            throw new ModelNotFoundException("No eixste un rol con id '$rolId'.");
        }
        return $rol;
    }
}
