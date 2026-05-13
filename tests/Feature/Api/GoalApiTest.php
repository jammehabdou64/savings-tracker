<?php

use App\Models\Deposit;
use App\Models\Goal;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('index requires authentication', function () {
    $this->getJson(route('api.goals.index'))->assertUnauthorized();
});

test('index returns only the authenticated users goals', function () {
    $user = User::factory()->create();
    Goal::factory()->for($user)->create(['name' => 'Mine']);
    Goal::factory()->for(User::factory())->create(['name' => 'Other']);

    Sanctum::actingAs($user);

    $this->getJson(route('api.goals.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Mine');
});

test('store creates a goal for the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(route('api.goals.store'), [
        'name' => 'New Bike',
        'target' => 1200,
        'deadline' => now()->addMonths(6)->toDateString(),
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'New Bike')
        ->assertJsonPath('data.target', 1200);

    $this->assertDatabaseHas('goals', [
        'user_id' => $user->id,
        'name' => 'New Bike',
    ]);
});

test('store validates input', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson(route('api.goals.store'), [
        'name' => '',
        'target' => 0,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'target']);
});

test('show returns goal with deposits for owner', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    Deposit::factory()->for($goal)->count(3)->create();

    Sanctum::actingAs($user);

    $this->getJson(route('api.goals.show', $goal))
        ->assertOk()
        ->assertJsonPath('data.id', $goal->id)
        ->assertJsonCount(3, 'data.deposits');
});

test('show forbids access to another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    Sanctum::actingAs($user);

    $this->getJson(route('api.goals.show', $goal))->assertForbidden();
});

test('update changes the goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create(['name' => 'Old']);

    Sanctum::actingAs($user);

    $this->putJson(route('api.goals.update', $goal), [
        'name' => 'New',
        'target' => 999,
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New');

    expect($goal->fresh()->name)->toBe('New');
});

test('update forbids another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    Sanctum::actingAs($user);

    $this->putJson(route('api.goals.update', $goal), [
        'name' => 'Hacked',
        'target' => 1,
    ])->assertForbidden();
});

test('destroy deletes own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson(route('api.goals.destroy', $goal))->assertNoContent();

    $this->assertModelMissing($goal);
});

test('destroy forbids another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    Sanctum::actingAs($user);

    $this->deleteJson(route('api.goals.destroy', $goal))->assertForbidden();
    $this->assertModelExists($goal);
});
