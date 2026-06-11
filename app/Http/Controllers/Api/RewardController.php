<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointsTransaction;
use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RewardController extends Controller
{
    public function index(Request $request)
    {
        $query = Reward::with('organization')
            ->where('active', true)
            ->orderByDesc('id');

        if ($request->filled('city_id')) {
            $cityId = (int) $request->city_id;
            // Platform-wide rewards (no org) + rewards from orgs in the selected city
            $query->where(function ($q) use ($cityId) {
                $q->whereNull('organization_id')
                  ->orWhereHas('organization', fn ($o) => $o->where('city_id', $cityId));
            });
        }

        $rewards = $query->get()
            ->filter(fn ($r) => $r->isValid())
            ->map(fn ($r) => [
                'id'              => $r->id,
                'title'           => $r->title,
                'description'     => $r->description,
                'photo_url'       => $r->photo_path ? url(Storage::url($r->photo_path)) : null,
                'points_required' => $r->points_required,
                'valid_from'      => $r->valid_from?->format('Y-m-d'),
                'valid_to'        => $r->valid_to?->format('Y-m-d'),
            ])
            ->values();

        return response()->json(['rewards' => $rewards]);
    }

    public function claim(Request $request, Reward $reward)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$reward->active || !$reward->isValid()) {
            return response()->json(['message' => 'Это вознаграждение недоступно'], 422);
        }

        if ($user->points_balance < $reward->points_required) {
            return response()->json([
                'message' => 'Недостаточно баллов',
                'required' => $reward->points_required,
                'current'  => $user->points_balance,
            ], 422);
        }

        DB::transaction(function () use ($user, $reward) {
            $newBalance = $user->points_balance - $reward->points_required;
            $user->update(['points_balance' => $newBalance]);
            PointsTransaction::create([
                'user_id'       => $user->id,
                'amount'        => -$reward->points_required,
                'balance_after' => $newBalance,
                'reason'        => 'Получение вознаграждения: ' . $reward->title,
                'admin_id'      => null,
            ]);
        });

        return response()->json([
            'message'       => 'Вознаграждение получено',
            'code'          => $reward->code,
            'balance_after' => $user->fresh()->points_balance,
        ]);
    }

    public function myPoints()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        return response()->json(['points_balance' => $user->points_balance]);
    }
}
