<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGoalRequest;
use App\Http\Requests\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GoalController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $goals = $request->user()->goals()
            ->with(['deposits' => fn ($q) => $q->orderByDesc('created_at')])
            ->orderByDesc('created_at')
            ->paginate(perPage: (int) $request->integer('per_page', 25));

        return GoalResource::collection($goals);
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $request->user()->goals()->create($request->validated());

        return GoalResource::make($goal)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Goal $goal): GoalResource
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->load(['deposits' => fn ($q) => $q->orderByDesc('created_at')]);

        return GoalResource::make($goal);
    }

    public function update(UpdateGoalRequest $request, Goal $goal): GoalResource
    {
        $goal->update($request->validated());

        return GoalResource::make($goal->refresh());
    }

    public function destroy(Request $request, Goal $goal): JsonResponse
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        $goal->delete();

        return response()->json(null, 204);
    }
}
