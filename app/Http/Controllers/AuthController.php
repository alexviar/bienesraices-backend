<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
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
    return response()->json(["message"=>__("auth.failed")], 401);
  }

  public function logout(Request $request){
    Auth::logout();
  }

  // public function createToken(Request $request)
  // {
  //   $request->validate([
  //     'username' => 'required',
  //     'password' => 'required',
  //     'device_name' => 'required',
  //   ]);

  //   $user = User::findByUsername($request->username);

  //   if (!$user || !Hash::check($request->password, $user->password)) {
  //     throw ValidationException::withMessages([
  //       'username' => ['The provided credentials are incorrect.'],
  //     ]);
  //   }

  //   return response()->json([
  //     "token" => $user->createToken($request->device_name)->plainTextToken
  //   ]);
  // }

  function change_password(Request $request)
  {
      $user = $request->user();
      $request->validate([
          "current_password" => "required|current_password",
          "password" => ["required", PasswordRule::min(8)
              ->letters()
              ->mixedCase()
              ->numbers()
              ->symbols()
          ],
          "password_confirmation" => "required|same:password",
      ], [
        "password_confirmation.required" => "La :attribute es requerida.",
        "password_confirmation.same" => "La :attribute no coincide."
      ]);
      
      $user->forceFill($request->only("password"));
      $user->save();
  }

  function forgot_password(Request $request)
  {
      $request->validate(['email' => 'required|email']);

      $status = Password::sendResetLink(
          $request->only('email')
      );
   
      return $status === Password::RESET_LINK_SENT
                  ? response()->json(['status' => __($status)])
                  : ValidationException::withMessages(['email' => __($status)]);
  }

  function reset_password(Request $request)
  {
      $request->validate([
          'token' => 'required',
          'email' => 'required|email',
          'password' => 'required|min:8|confirmed',
      ]);
   
      $status = Password::reset(
          $request->only('email', 'password', 'password_confirmation', 'token'),
          function (User $user, $password) {
              $user->forceFill([
                  'password' => $password
              ])->setRememberToken(Str::random(60));
   
              $user->save();
   
              event(new PasswordReset($user));
          }
      );
   
      return $status === Password::PASSWORD_RESET
                  ? response()->json('status', __($status))
                  : ValidationException::withMessages(['email' => __($status)]);
  }
}
