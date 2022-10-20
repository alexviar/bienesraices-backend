<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// it("verifica que el usuario existe", function(){
//     /** @var TestCase $this */
//     $user = User::find(1);
//     $response = $this->actingAs($user)->postJson("/api/user/change-password", []);
//     $response->assertNotFound();
// });

test("solo usuarios autenticados", function(){
    /** @var TestCase $this */
    $response = $this->postJson("/change-password", []);
    $response->assertUnauthorized();
});

it("valida los campos requeridos", function(){
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson("/change-password", []);
    $response->assertJsonValidationErrors([
        "current_password" => "El campo 'contraseña actual' es requerido.",
        "password" => "El campo 'contraseña' es requerido.",
        "password_confirmation" => "La confirmación de la contraseña es requerida."
    ]);

});

test("la contraseña actual no coincide", function(){
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory([
        "password" => "1234"
    ])->create();
    $response = $this->actingAs($user)->postJson("/change-password", [
        "current_password" => "0000"
    ]);
    $response->assertJsonValidationErrors([
        "current_password" => "La contraseña es incorrecta."
    ]);
});

test("confirmacion de contraseña", function(){
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson("/change-password", [
        "password" => "0000",
        "password_confirmation" => "1234"
    ]);
    $response->assertJsonValidationErrors([
        "password_confirmation" => "La confirmación de la contraseña no coincide."
    ]);
});

test("contraseña invalida", function($password){
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson("/change-password", [
        "password" => $password,
    ]);
    $response->assertJsonValidationErrorFor("password");
})->with([
    "0000",
    "asdf",
    "%&%/",
    "aS1$",
    "asdf1234%&/(",
    "ASDF,1234",
]);

it("cambia la contraseña", function(){
    /** @var TestCase $this */
    /** @var User $user */
    $user = User::factory()->create();
    $response = $this->actingAs($user)->postJson("/change-password", [
        "current_password" => "password",
        "password" => "asDF12·$",
        "password_confirmation" => "asDF12·$",
    ]);
    $response->assertOk();
    $user->refresh();
    expect(Hash::check("asDF12·$", $user->password))->toBeTrue();
});