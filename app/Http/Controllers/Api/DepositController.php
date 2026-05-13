<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepositRequest;
use App\Http\Resources\DepositResource;
use App\Models\Deposit;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DepositController extends Controller
{
    public function index(Request $request, Goal $goal): AnonymousResourceCollection
    {
        abort_unless($goal->user_id === $request->user()->id, 403);

        return DepositResource::collection(
            $goal->deposits()
                ->orderByDesc('created_at')
                ->paginate(perPage: (int) $request->integer('per_page', 50)),
        );
    }

    public function store(StoreDepositRequest $request, Goal $goal): JsonResponse
    {
        $deposit = $goal->deposits()->create($request->validated());

        return DepositResource::make($deposit)
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Goal $goal, Deposit $deposit): JsonResponse
    {
        abort_unless(
            $goal->user_id === $request->user()->id && $deposit->goal_id === $goal->id,
            403,
        );

        $deposit->delete();

        return response()->json(null, 204);
    }
}
