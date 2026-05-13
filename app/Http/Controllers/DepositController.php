<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepositRequest;
use App\Models\Deposit;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DepositController extends Controller
{
    public function store(StoreDepositRequest $request, Goal $goal): RedirectResponse
    {
        $goal->deposits()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Deposit added.']);

        return back();
    }

    public function destroy(Request $request, Goal $goal, Deposit $deposit): RedirectResponse
    {
        abort_unless(
            $goal->user_id === $request->user()->id && $deposit->goal_id === $goal->id,
            403,
        );

        $deposit->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Deposit removed.']);

        return back();
    }
}
