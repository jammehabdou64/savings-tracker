<?php

use App\Models\Deposit;
use App\Models\Goal;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('dashboard requires authentication', function () {
    $this->getJson(route('api.dashboard'))->assertUnauthorized();
});

test('dashboard returns summary, monthly deposits, and goals for the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $completed = Goal::factory()->for($user)->create(['name' => 'Mine A', 'target' => 100]);
    Deposit::factory()->for($completed)->create(['amount' => 100, 'created_at' => '2026-03-01 09:00:00']);

    $active = Goal::factory()->for($user)->create(['name' => 'Mine B', 'target' => 500]);
    Deposit::factory()->for($active)->create(['amount' => 150, 'created_at' => '2026-04-01 09:00:00']);
    Deposit::factory()->for($active)->create(['amount' => 50, 'created_at' => '2026-04-15 09:00:00']);

    Goal::factory()->for($other)->create(['name' => 'Not Mine']);

    Sanctum::actingAs($user);

    $response = $this->getJson(route('api.dashboard'))->assertOk();

    $response->assertJsonPath('data.summary.total_savings', 300)
        ->assertJsonPath('data.summary.active_goals', 1)
        ->assertJsonPath('data.summary.completed_goals', 1)
        ->assertJsonCount(2, 'data.goals')
        ->assertJsonCount(2, 'data.monthly_deposits');

    $months = collect($response->json('data.monthly_deposits'));
    expect($months->firstWhere('month', '2026-03')['total'])->toEqual(100);
    expect($months->firstWhere('month', '2026-04')['total'])->toEqual(200);
});

test('dashboard returns empty payload for a new user', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson(route('api.dashboard'))
        ->assertOk()
        ->assertJsonPath('data.summary.total_savings', 0)
        ->assertJsonPath('data.summary.active_goals', 0)
        ->assertJsonPath('data.summary.completed_goals', 0)
        ->assertJsonCount(0, 'data.goals')
        ->assertJsonCount(0, 'data.monthly_deposits');
});
