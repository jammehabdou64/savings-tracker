<?php

namespace App\Models;

use Database\Factories\GoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'name', 'target', 'deadline'])]
class Goal extends Model
{
    /** @use HasFactory<GoalFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target' => 'decimal:2',
            'deadline' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function totalSaved(): float
    {
        return (float) $this->deposits->sum('amount');
    }

    public function progressPercentage(): float
    {
        $target = (float) $this->target;

        if ($target <= 0) {
            return 0;
        }

        return min(100, round(($this->totalSaved() / $target) * 100, 1));
    }

    public function isCompleted(): bool
    {
        return $this->totalSaved() >= (float) $this->target;
    }

    public function isNotStarted(): bool
    {
        return $this->deposits->isEmpty();
    }
}
