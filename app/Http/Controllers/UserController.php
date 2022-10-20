<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    private function findUser($userId)
    {
        $user = User::find($userId);
        if(!$user){
            throw new ModelNotFoundException("No existe un usuario con id $userId");
        }
        return $user;
    }
}
