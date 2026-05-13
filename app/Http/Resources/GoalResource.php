<?php

namespace App\Http\Resources;

use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Goal
 */
class GoalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target' => (float) $this->target,
            'deadline' => $this->deadline?->toDateString(),
            'saved' => $this->totalSaved(),
            'progress' => $this->progressPercentage(),
            'is_completed' => $this->isCompleted(),
            'is_not_started' => $this->isNotStarted(),
            'deposit_count' => $this->whenLoaded(
                'deposits',
                fn () => $this->deposits->count(),
            ),
            'deposits' => DepositResource::collection(
                $this->whenLoaded('deposits'),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
