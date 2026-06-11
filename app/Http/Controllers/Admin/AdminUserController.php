<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    use AdminAccess;

    private array $roleLabels = [
        'admin' => 'Главный админ',
        'super_admin' => 'Главный админ',
        'org_admin' => 'Админ ЖКХ',
        'worker' => 'Сотрудник',
        'resident' => 'Житель',
    ];

    public function index(Request $request)
    {
        $admin = $this->requireSuperAdmin();

        $query = User::with('organization')->withCount(['tickets', 'assignedTickets']);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        if ($request->filled('state')) {
            if ($request->state === 'banned') {
                $query->whereNotNull('banned_at');
            }
            if ($request->state === 'active') {
                $query->whereNull('banned_at');
            }
        }

        $users = $query
            ->orderByRaw("FIELD(role, 'super_admin', 'admin', 'org_admin', 'worker', 'resident')")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $organizations = Organization::where('active', true)->orderBy('id')->get();
        $roleLabels = $this->roleLabels;

        $summary = [
            'total' => User::count(),
            'residents' => User::where('role', 'resident')->count(),
            'workers' => User::where('role', 'worker')->count(),
            'org_admins' => User::where('role', 'org_admin')->count(),
            'super_admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'banned' => User::whereNotNull('banned_at')->count(),
        ];

        return view('admin.users.index', compact('users', 'organizations', 'roleLabels', 'summary', 'admin'));
    }

    public function ban(Request $request, User $user)
    {
        $admin = $this->requireSuperAdmin();

        if ($user->id === $admin->id) {
            return back()->withErrors(['user' => 'Нельзя заблокировать собственный аккаунт.']);
        }

        if ($this->isSuperAdmin($user)) {
            return back()->withErrors(['user' => 'Главного админа нельзя заблокировать из общего реестра.']);
        }

        $data = $request->validate([
            'ban_reason' => ['required', 'string', 'max:255'],
        ]);

        $user->update([
            'banned_at' => now(),
            'ban_reason' => $data['ban_reason'],
            'banned_by_user_id' => Auth::id(),
        ]);

        return back()->with('success', 'Пользователь заблокирован');
    }

    public function unban(User $user)
    {
        $this->requireSuperAdmin();

        if ($this->isSuperAdmin($user)) {
            return back()->withErrors(['user' => 'Главный админ не должен проходить через обычную блокировку.']);
        }

        $user->update([
            'banned_at' => null,
            'ban_reason' => null,
            'banned_by_user_id' => null,
        ]);

        return back()->with('success', 'Пользователь разблокирован');
    }
}
