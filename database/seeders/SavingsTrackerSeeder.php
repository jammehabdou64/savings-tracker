<?php

namespace Database\Seeders;

use App\Models\Goal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SavingsTrackerSeeder extends Seeder
{
    /**
     * Seed a demo user populated from database/seed-data.json.
     *
     * Dates in seed-data.json are anchored to March 2026. This seeder shifts
     * every timestamp forward so the data still looks current relative to today.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ],
        );

        $user->goals()->delete();

        $payload = json_decode(
            file_get_contents(database_path('seed-data.json')),
            associative: true,
        );

        $monthOffset = $this->monthOffsetFromMarch2026();

        foreach ($payload['goals'] as $goalData) {
            $createdAt = $this->shift($goalData['createdAt'], $monthOffset);

            $goal = $user->goals()->create([
                'name' => $goalData['name'],
                'target' => $goalData['target'],
                'deadline' => $this->shiftDate($goalData['deadline'], $monthOffset),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($goalData['deposits'] as $depositData) {
                $depositCreatedAt = $this->shift($depositData['createdAt'], $monthOffset);

                $goal->deposits()->create([
                    'amount' => $depositData['amount'],
                    'note' => $depositData['note'] ?: null,
                    'created_at' => $depositCreatedAt,
                    'updated_at' => $depositCreatedAt,
                ]);
            }
        }
    }

    private function monthOffsetFromMarch2026(): int
    {
        $anchor = CarbonImmutable::create(2026, 3, 1);
        $now = CarbonImmutable::now()->startOfMonth();

        return $anchor->diffInMonths($now, false);
    }

    private function shift(string $iso, int $months): CarbonImmutable
    {
        return CarbonImmutable::parse($iso)->addMonths($months);
    }

    private function shiftDate(?string $date, int $months): ?string
    {
        if ($date === null) {
            return null;
        }

        return CarbonImmutable::parse($date)->addMonths($months)->toDateString();
    }
}
