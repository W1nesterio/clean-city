<?php

namespace App\Services;

use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PointsService
{
    public static function award(User $user, int $amount, string $reason): void
    {
        if ($amount <= 0) return;

        DB::transaction(function () use ($user, $amount, $reason) {
            $newBalance = $user->points_balance + $amount;
            $user->update(['points_balance' => $newBalance]);
            PointsTransaction::create([
                'user_id'       => $user->id,
                'amount'        => $amount,
                'balance_after' => $newBalance,
                'reason'        => $reason,
                'admin_id'      => null,
            ]);
        });
    }

    public static function pointsForCompletion(): int
    {
        $value = DB::table('system_settings')
            ->where('key', 'points_per_ticket_completion')
            ->value('value');

        return (int) ($value ?? 10);
    }

    public static function pointsForCreation(): int
    {
        $value = DB::table('system_settings')
            ->where('key', 'points_per_ticket_creation')
            ->value('value');

        return (int) ($value ?? 5);
    }
}
