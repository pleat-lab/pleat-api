<?php

use App\Enums\UserRole;
use App\Models\User;

it('registra un customer y devuelve su token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Priya Raghavan',
        'email' => 'priya@correo.com',
        'password' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'priya@correo.com')
        ->assertJsonPath('data.role', 'customer')
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role'], 'token']);

    expect(User::first()->role)->toBe(UserRole::Customer);
});

it('guarda el correo en minúsculas al registrar', function () {
    $this->postJson('/api/register', [
        'name' => 'Priya Raghavan',
        'email' => 'Priya@Correo.COM',
        'password' => 'password',
    ])->assertCreated()->assertJsonPath('data.email', 'priya@correo.com');
});

it('no deja que el registro asigne un rol', function () {
    $this->postJson('/api/register', [
        'name' => 'Intruso',
        'email' => 'intruso@correo.com',
        'password' => 'password',
        'role' => 'admin',
    ])->assertCreated()->assertJsonPath('data.role', 'customer');
});

it('inicia sesión con credenciales correctas', function () {
    User::factory()->create(['email' => 'mei@pleat.app']);

    $this->postJson('/api/login', [
        'email' => 'mei@pleat.app',
        'password' => 'password',
    ])->assertOk()
        ->assertJsonPath('data.email', 'mei@pleat.app')
        ->assertJsonStructure(['token']);
});

it('inicia sesión aunque el correo venga en mayúsculas', function () {
    User::factory()->create(['email' => 'mei@pleat.app']);

    $this->postJson('/api/login', [
        'email' => 'MEI@Pleat.App',
        'password' => 'password',
    ])->assertOk()->assertJsonPath('data.email', 'mei@pleat.app');
});

it('rechaza credenciales incorrectas con 422 y mensaje en español', function () {
    User::factory()->create(['email' => 'mei@pleat.app']);

    $this->postJson('/api/login', [
        'email' => 'mei@pleat.app',
        'password' => 'otra-contraseña',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Las credenciales son incorrectas.']);
});

it('bloquea el login con 429 al sexto intento en un minuto', function () {
    $credentials = ['email' => 'nadie@pleat.app', 'password' => 'incorrecta'];

    foreach (range(1, 5) as $intento) {
        $this->postJson('/api/login', $credentials)->assertUnprocessable();
    }

    $this->postJson('/api/login', $credentials)->assertTooManyRequests();
});

it('cierra sesión revocando el token actual', function () {
    $user = User::factory()->create();

    // Se usa un token real (no actingAs) para probar que se borra de la base.
    $token = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->json('token');

    expect($user->tokens()->count())->toBe(1);

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

it('devuelve el usuario autenticado en /me', function () {
    $user = User::factory()->staff()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/me')
        ->assertOk()
        ->assertJson(['data' => [
            'id' => $user->id,
            'email' => $user->email,
            'role' => 'staff',
        ]]);
});

it('responde 401 en /me sin token', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});
