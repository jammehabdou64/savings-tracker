<?php

use App\Http\Controllers\DepositController;
use App\Http\Controllers\GoalController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [GoalController::class, 'index'])->name('dashboard');

    Route::post('goals', [GoalController::class, 'store'])->name('goals.store');
    Route::get('goals/{goal}', [GoalController::class, 'show'])->name('goals.show');
    Route::put('goals/{goal}', [GoalController::class, 'update'])->name('goals.update');
    Route::delete('goals/{goal}', [GoalController::class, 'destroy'])->name('goals.destroy');

    Route::post('goals/{goal}/deposits', [DepositController::class, 'store'])->name('deposits.store');
    Route::delete('goals/{goal}/deposits/{deposit}', [DepositController::class, 'destroy'])->name('deposits.destroy');
});

require __DIR__.'/settings.php';
