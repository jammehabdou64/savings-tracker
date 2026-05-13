<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a new user can register and receive a token', function () {
    $this->postJson(route('api.register'), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'secret-pw1',
        'password_confirmation' => 'secret-pw1',
        'device_name' => 'iphone',
    ])
        ->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('user.email', 'ada@example.com');

    $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
    $this->assertDatabaseCount('personal_access_tokens', 1);
});

test('registration rejects duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson(route('api.register'), [
        'name' => 'Other',
        'email' => 'taken@example.com',
        'password' => 'secret-pw1',
        'password_confirmation' => 'secret-pw1',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('a user can log in and receive a token', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'correct-horse',
    ]);

    $this->postJson(route('api.login'), [
        'email' => 'user@example.com',
        'password' => 'correct-horse',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user']);
});

test('login fails with bad credentials', function () {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => 'correct-horse',
    ]);

    $this->postJson(route('api.login'), [
        'email' => 'user@example.com',
        'password' => 'wrong',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

test('me returns the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson(route('api.me'))
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email);
});

test('me requires a token', function () {
    $this->getJson(route('api.me'))->assertUnauthorized();
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.logout'))
        ->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});
