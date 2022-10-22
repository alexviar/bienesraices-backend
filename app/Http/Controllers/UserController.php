<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
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

    function store(Request $request)
    {
        $this->authorize("create", [User::class, $request->all()]);
        $payload = $request->validate([
            "username" => "required|unique:users",
            "email" => "required|email|unique:users",
            "password" => ["required", Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
            ],
            "roles" => "required",
            "roles.*" => [Rule::exists("roles", "name")->where("guard_name", "sanctum")]
        ], [], [
            "roles.*" => "rol"
        ]);

        $user = DB::transaction(function() use($payload){
            $user = User::create(Arr::except($payload, "roles"));
            $user->syncRoles(Arr::only($payload, "roles"));
            return $user;
        });
        return $user;
    }


    private function findUser($userId)
    {
        $user = User::find($userId);
        if(!$user){
            throw new ModelNotFoundException("No existe un usuario con id $userId");
        }
        return $user;
    }
}
