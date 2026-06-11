<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\PointsTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminPointsController extends Controller
{
    use AdminAccess;

    public function index(Request $request)
    {
        $this->requireSuperAdmin();

        $query = User::where('role', 'resident');

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->orderByDesc('points_balance')->orderBy('name')->paginate(25)->withQueryString();

        return view('admin.points.index', compact('users'));
    }

    public function adjust(Request $request, User $user)
    {
        $admin = $this->requireSuperAdmin();

        $data = $request->validate([
            'action' => ['required', 'in:add,subtract,set'],
            'amount' => ['required', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $user, $admin) {
            $old = $user->points_balance;

            $newBalance = match ($data['action']) {
                'add'      => $old + $data['amount'],
                'subtract' => max(0, $old - $data['amount']),
                'set'      => $data['amount'],
            };

            $delta = $newBalance - $old;

            $user->update(['points_balance' => $newBalance]);

            PointsTransaction::create([
                'user_id'      => $user->id,
                'amount'       => $delta,
                'balance_after'=> $newBalance,
                'reason'       => $data['reason'],
                'admin_id'     => $admin->id,
            ]);
        });

        return back()->with('success', 'Баллы пользователя обновлены');
    }

    public function history(User $user)
    {
        $this->requireSuperAdmin();

        $transactions = PointsTransaction::with('admin')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.points.history', compact('user', 'transactions'));
    }
}
