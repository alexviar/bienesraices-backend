<?php

namespace App\Http\Controllers;

use App\Application\TransferenciaExternaService;
use App\Application\UserService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

  /**
   * Handle an authentication attempt.
   *
   * @param  \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function login(Request $request)
  {
    $credentials = $request->validate([
      'username' => 'required',
      'password' => 'required',
    ]);

    $remember_me =  $request->remember_me;

    if (Auth::attempt($credentials+["estado"=>1], $remember_me)) {
      $request->session()->regenerate();

      // var_dump(Auth::user());
      /** @var User $user */
      $user = Auth::user();
      // $user->load(["roles.permissions"]);
      return response()->json(Auth::user());
    }

    // abort(401, __("passwords.credentials"));
    // throw UnauthorizedException(__("passwords.credentials"))
    return response()->json(["message"=>__("passwords.credentials")], 401);
  }

  public function logout(Request $request){
    Auth::logout();
  }

  public function createToken(Request $request)
  {
    $request->validate([
      'username' => 'required',
      'password' => 'required',
      'device_name' => 'required',
    ]);

    $user = User::findByUsername($request->username);

    if (!$user || !Hash::check($request->password, $user->password)) {
      throw ValidationException::withMessages([
        'username' => ['The provided credentials are incorrect.'],
      ]);
    }

    return response()->json([
      "token" => $user->createToken($request->device_name)->plainTextToken
    ]);
  }
}
