<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\GoalController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register'])
    ->middleware('throttle:6,1')
    ->name('api.register');

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('api.login');

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('me', [AuthController::class, 'me'])->name('me');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::apiResource('goals', GoalController::class);

    Route::get('goals/{goal}/deposits', [DepositController::class, 'index'])
        ->name('goals.deposits.index');
    Route::post('goals/{goal}/deposits', [DepositController::class, 'store'])
        ->name('goals.deposits.store');
    Route::delete('goals/{goal}/deposits/{deposit}', [DepositController::class, 'destroy'])
        ->name('goals.deposits.destroy');
});
