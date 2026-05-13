<?php

use App\Models\Deposit;
use App\Models\Goal;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('index returns deposits for owner', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    Deposit::factory()->for($goal)->count(2)->create();

    Sanctum::actingAs($user);

    $this->getJson(route('api.goals.deposits.index', $goal))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('index forbids another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    Sanctum::actingAs($user);

    $this->getJson(route('api.goals.deposits.index', $goal))->assertForbidden();
});

test('store creates a deposit', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->postJson(route('api.goals.deposits.store', $goal), [
        'amount' => 125.50,
        'note' => 'Side gig',
    ])
        ->assertCreated()
        ->assertJsonPath('data.amount', 125.50)
        ->assertJsonPath('data.note', 'Side gig');

    $this->assertDatabaseHas('deposits', [
        'goal_id' => $goal->id,
        'amount' => 125.50,
    ]);
});

test('store rejects zero or negative amounts', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->postJson(route('api.goals.deposits.store', $goal), ['amount' => 0])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

test('store forbids depositing into another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    Sanctum::actingAs($user);

    $this->postJson(route('api.goals.deposits.store', $goal), ['amount' => 50])
        ->assertForbidden();
});

test('destroy deletes own deposit', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $deposit = Deposit::factory()->for($goal)->create();

    Sanctum::actingAs($user);

    $this->deleteJson(route('api.goals.deposits.destroy', [$goal, $deposit]))
        ->assertNoContent();

    $this->assertModelMissing($deposit);
});

test('destroy forbids another users deposit', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();
    $deposit = Deposit::factory()->for($goal)->create();

    Sanctum::actingAs($user);

    $this->deleteJson(route('api.goals.deposits.destroy', [$goal, $deposit]))
        ->assertForbidden();
});
