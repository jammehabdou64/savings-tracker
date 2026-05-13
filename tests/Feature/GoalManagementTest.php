<?php

use App\Models\Deposit;
use App\Models\Goal;
use App\Models\User;

test('guests cannot access the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('dashboard shows only the authenticated users goals', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $myGoal = Goal::factory()->for($user)->create(['name' => 'My Goal']);
    Goal::factory()->for($other)->create(['name' => 'Other Goal']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('goals/index')
            ->has('goals', 1)
            ->where('goals.0.name', 'My Goal')
            ->where('goals.0.id', $myGoal->id)
        );
});

test('user can create a goal', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goals.store'), [
            'name' => 'New Laptop',
            'target' => 1500,
            'deadline' => '2027-01-01',
        ])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('goals', [
        'user_id' => $user->id,
        'name' => 'New Laptop',
        'target' => 1500,
    ]);
});

test('creating a goal requires name and target', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goals.store'), [])
        ->assertSessionHasErrors(['name', 'target']);
});

test('target must be positive', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('goals.store'), [
            'name' => 'Bad Goal',
            'target' => 0,
        ])
        ->assertSessionHasErrors('target');
});

test('user can update their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create(['name' => 'Old name']);

    $this->actingAs($user)
        ->put(route('goals.update', $goal), [
            'name' => 'New name',
            'target' => 2000,
            'deadline' => null,
        ])
        ->assertRedirect();

    expect($goal->fresh())->name->toBe('New name')->target->toEqual('2000.00');
});

test('user cannot update another users goal', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $goal = Goal::factory()->for($other)->create();

    $this->actingAs($user)
        ->put(route('goals.update', $goal), [
            'name' => 'Hacked',
            'target' => 1,
        ])
        ->assertForbidden();
});

test('user can delete their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('goals.destroy', $goal))
        ->assertRedirect(route('dashboard'));

    $this->assertModelMissing($goal);
});

test('user cannot delete another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->delete(route('goals.destroy', $goal))
        ->assertForbidden();

    $this->assertModelExists($goal);
});

test('user can view their own goal details', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    Deposit::factory()->for($goal)->create(['amount' => 250]);

    $this->actingAs($user)
        ->get(route('goals.show', $goal))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('goals/show')
            ->where('goal.id', $goal->id)
            ->where('goal.saved', 250)
            ->has('goal.deposits', 1)
        );
});

test('user cannot view another users goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for(User::factory())->create();

    $this->actingAs($user)
        ->get(route('goals.show', $goal))
        ->assertForbidden();
});
