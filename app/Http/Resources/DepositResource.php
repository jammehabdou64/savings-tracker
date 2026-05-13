<?php

namespace App\Http\Resources;

use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Deposit
 */
class DepositResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'goal_id' => $this->goal_id,
            'amount' => (float) $this->amount,
            'note' => $this->note,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
