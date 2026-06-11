<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AdminAccess;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketHide;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    use AdminAccess;

    public function index()
    {
        $admin = $this->requireAdmin();

        if ($this->isSuperAdmin($admin)) {
            $summary = [
                'users' => User::count(),
                'banned' => User::whereNotNull('banned_at')->count(),
                'residents' => User::where('role', 'resident')->count(),
                'org_admins' => User::where('role', 'org_admin')->count(),
                'workers' => User::where('role', 'worker')->count(),
                'organizations' => Organization::where('active', true)->count(),
            ];

            $organizations = Organization::withCount([
                'admins',
                'workers',
                'tickets' => fn ($q) => $q->whereNull('deleted_at'),
            ])->where('active', true)->orderBy('id')->get();

            $bannedUsers = User::whereNotNull('banned_at')
                ->latest('banned_at')
                ->limit(8)
                ->get();

            $recentUsers = User::latest()->limit(8)->get();

            return view('admin.dashboard', compact('admin', 'summary', 'organizations', 'bannedUsers', 'recentUsers'));
        }

        $orgId = $admin->organization_id;
        $baseQuery = Ticket::query()->where('assigned_org_id', $orgId)->whereNull('deleted_at');
        $this->scopeTicketsForAdmin($baseQuery, $admin);

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'new' => (clone $baseQuery)->whereIn('status', ['created', 'moderation', 'assigned'])->count(),
            'active' => (clone $baseQuery)->whereIn('status', ['accepted', 'in_progress', 'problem', 'postponed'])->count(),
            'completed' => (clone $baseQuery)->where('status', 'completed')->count(),
            'hidden' => TicketHide::where('organization_id', $orgId)->where('active', true)->count(),
            'workers' => User::where('role', 'worker')->where('organization_id', $orgId)->count(),
        ];

        $attentionTickets = Ticket::with(['category', 'assignedWorker'])
            ->where('assigned_org_id', $orgId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['assigned', 'accepted', 'in_progress', 'problem', 'postponed'])
            ->whereDoesntHave('activeHides', fn ($q) => $q->where('organization_id', $orgId))
            ->latest()
            ->limit(10)
            ->get();

        $recentFinishedTickets = Ticket::with(['category', 'assignedWorker'])
            ->where('assigned_org_id', $orgId)
            ->whereNull('deleted_at')
            ->whereIn('status', ['completed', 'rejected', 'duplicate'])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('admin', 'summary', 'attentionTickets', 'recentFinishedTickets'));
    }
}
