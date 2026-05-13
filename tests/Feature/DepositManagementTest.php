<?php

use App\Models\Deposit;
use App\Models\Goal;
use App\Models\User;

test('user can add a deposit to their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('deposits.store', $goal), [
            'amount' => 75.50,
            'note' => 'Freelance bonus',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('deposits', [
        'goal_id' => $goal->id,
        'amount' => 75.50,
        'note' => 'Freelance bonus',
    ]);
});

test('deposit amount must be greater than zero', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    $this->actingAs($user)
        ->post(route('deposits.store', $goal), [
            'amount' => 0,
        ])
        ->assertSessionHasErrors('amount');

    $this->actingAs($user)
        ->post(route('deposits.store', $goal), [
            'amount' => -10,
        ])
        ->assertSessionHasErrors('amount');
});

test('user cannot deposit into another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->post(route('deposits.store', $goal), [
            'amount' => 100,
        ])
        ->assertForbidden();
});

test('user can remove their own deposit', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $deposit = Deposit::factory()->for($goal)->create();

    $this->actingAs($user)
        ->delete(route('deposits.destroy', [$goal, $deposit]))
        ->assertRedirect();

    $this->assertModelMissing($deposit);
});

test('user cannot remove another users deposit', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();
    $deposit = Deposit::factory()->for($goal)->create();

    $this->actingAs($user)
        ->delete(route('deposits.destroy', [$goal, $deposit]))
        ->assertForbidden();

    $this->assertModelExists($deposit);
});

test('deleting a goal cascades to its deposits', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $deposit = Deposit::factory()->for($goal)->create();

    $goal->delete();

    $this->assertModelMissing($deposit);
});
