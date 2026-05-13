<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Return the authenticated user's dashboard payload:
     * summary stats, monthly deposit totals, and the goals list.
     */
    public function __invoke(Request $request): JsonResponse
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

        return response()->json([
            'data' => [
                'summary' => [
                    'total_savings' => (float) $goals->sum(fn (Goal $g) => $g->totalSaved()),
                    'active_goals' => $goals->reject(fn (Goal $g) => $g->isCompleted())->count(),
                    'completed_goals' => $goals->filter(fn (Goal $g) => $g->isCompleted())->count(),
                ],
                'monthly_deposits' => $monthlyDeposits,
                'goals' => GoalResource::collection($goals)->resolve($request),
            ],
        ]);
    }
}
