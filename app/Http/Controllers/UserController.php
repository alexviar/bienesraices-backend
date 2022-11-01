<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    function index(Request $request)
    {
        $this->authorize("viewAny", [User::class, $request->all()]);
        $queryArgs =  $request->only(["search", "filter", "page"]);
        return $this->buildResponse(User::query()->latest(), $queryArgs);
    }

    function show($userId){
        $user = $this->findUser($userId);
        $this->authorize("view", [$user]);
        return $user->loadMissing(["vendedor","proyectos","roles"]);
    }

    function store(Request $request)
    {
        $this->authorize("create", [User::class, $request->all()]);
        $payload = $request->validate([
            "username" => "required|unique:users|regex:/^[a-zA-Z][a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?$/",
            "email" => "required|email|unique:users",
            "password" => [
                "required", Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            "roles" => "required|array",
            "roles.*" => [Rule::exists("roles", "name")->where("guard_name", "sanctum")],
            "vendedor_id" => "nullable|exists:vendedores,id",
            "proyecto_ids" => "nullable|array",
            "proyecto_ids.*" => "exists:proyectos,id"
        ], [], [
            "roles.*" => "rol",
            "vendedor_id" => "vendedor",
            "proyecto_ids" => "proyectos"
        ]);

        $user = DB::transaction(function () use ($payload) {
            $user = User::create(Arr::except($payload, "roles", "proyecto_ids"));
            $user->syncRoles(Arr::only($payload, "roles"));
            if ($proyectoIds = Arr::get($payload, "proyecto_ids")) {
                $user->proyectos()->sync($proyectoIds);
            }

            // event(new Registered($user));
            return $user;
        });
        return $user;
    }

    function update(Request $request, $userId)
    {
        $user = $this->findUser($userId);
        $this->authorize("update", [$user, $request->all()]);
        $payload = $request->validate([
            "username" => "sometimes|required|unique:users,username,$userId|regex:/^[a-zA-Z][a-zA-Z0-9]*(\.[a-zA-Z0-9]+)?$/",
            "email" => "sometimes|required|email|unique:users,email,$userId",
            "password" => [
                "sometimes",
                "required",
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            "roles" => "sometimes|required|array",
            "roles.*" => ["sometimes", Rule::exists("roles", "name")->where("guard_name", "sanctum")],
            "vendedor_id" => "sometimes|nullable|exists:vendedores,id",
            "proyecto_ids" => "sometimes|nullable|array",
            "proyecto_ids.*" => "sometimes|exists:proyectos,id"
        ], [], [
            "roles.*" => "rol",
            "vendedor_id" => "vendedor",
            "proyecto_ids" => "proyectos"
        ]);

        $user = DB::transaction(function () use ($user, $payload) {
            $user->update(Arr::except($payload, "roles", "proyecto_ids"));
            if($roles = Arr::get($payload, "roles")){
                $user->syncRoles($roles);
            }
            if ($proyectoIds = Arr::get($payload, "proyecto_ids")) {
                $user->proyectos()->sync($proyectoIds);
            }
            return $user;
        });
        return $user;
    }

    public function changeStatus(Request $request, $id, $action){
        $user = $this->findUser($id);
        $this->authorize("changeStatus", [$user, $action]);
        switch($action){
            case "activar":
                $user->forceFill([
                    "estado" => 1
                ])->update();
                break;
            case "desactivar":
                $user->forceFill([
                    "estado" => 2
                ])->update();
                break;
        }
    }

    /**
     * @return User
     */
    private function findUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new ModelNotFoundException("No existe un usuario con id $userId");
        }
        return $user;
    }
}
