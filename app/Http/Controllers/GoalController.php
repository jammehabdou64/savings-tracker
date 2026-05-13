<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Models\Goal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GoalController extends Controller
{
    /**
     * Show the dashboard with goals, summary, and chart data.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $goals = $user->goals()
            ->with(['deposits' => fn ($q) => $q->orderByDesc('created_at')])
            ->orderByDesc('created_at')
            ->get();

        $monthlyDeposits = DB::table('deposits')
            ->join('goals', 'goals.id', '=', 'deposits.goal_id')
            ->where('goals.user_id', $user->id)
            ->selectRaw("strftime('%Y-%m', deposits.created_at) as month, SUM(deposits.amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'total' => (float) $row->total,
            ]);

        return Inertia::render('goals/index', [
            'goals' => $goals->map(fn (Goal $goal) => $this->transformGoal($goal)),
            'summary' => [
                'totalSavings' => (float) $goals->sum(fn (Goal $g) => $g->totalSaved()),
                'activeGoals' => $goals->reject(fn (Goal $g) => $g->isCompleted())->count(),
                'completedGoals' => $goals->filter(fn (Goal $g) => $g->isCompleted())->count(),
            ],
            'monthlyDeposits' => $monthlyDeposits,
        ]);
    }

    /**
     * Show a single goal with full deposit history.
     */
    public function show(Request $request, Goal $goal): Response
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->load(['deposits' => fn ($q) => $q->orderByDesc('created_at')]);

        return Inertia::render('goals/show', [
            'goal' => $this->transformGoal($goal, withDeposits: true),
        ]);
    }

    public function store(StoreGoalRequest $request): RedirectResponse
    {
        $request->user()->goals()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Goal created.']);

        return to_route('dashboard');
    }

    public function update(UpdateGoalRequest $request, Goal $goal): RedirectResponse
    {
        $goal->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Goal updated.']);

        return back();
    }

    public function destroy(Request $request, Goal $goal): RedirectResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Goal deleted.']);

        return to_route('dashboard');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformGoal(Goal $goal, bool $withDeposits = false): array
    {
        $saved = $goal->totalSaved();
        $target = (float) $goal->target;

        $data = [
            'id' => $goal->id,
            'name' => $goal->name,
            'target' => $target,
            'deadline' => $goal->deadline?->toDateString(),
            'createdAt' => $goal->created_at?->toIso8601String(),
            'saved' => $saved,
            'progress' => $goal->progressPercentage(),
            'isCompleted' => $goal->isCompleted(),
            'isNotStarted' => $goal->isNotStarted(),
            'depositCount' => $goal->deposits->count(),
        ];

        if ($withDeposits) {
            $data['deposits'] = $goal->deposits->map(fn ($d) => [
                'id' => $d->id,
                'amount' => (float) $d->amount,
                'note' => $d->note,
                'createdAt' => $d->created_at?->toIso8601String(),
            ])->values();
        }

        return $data;
    }
}
